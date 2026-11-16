<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The automated intake flags a legitimate report as review or spam, and the
 * resident's complaint is then invisible on the register forever. The
 * administrator is the only person who may overrule the flag, and the
 * overrule is a decision a person makes — recorded in the audit trail.
 */
class ClearSpamFlagTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'can_verify' => true,
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_the_admin_can_release_a_review_flag(): void
    {
        $complaint = $this->flaggedComplaint('review');

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'Reviewed the photograph and the text — this is a genuine report filed in Daet.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint));

        $this->assertSame('clear', $complaint->fresh()->spam_status);
        $this->assertTrue($this->auditHas('complaint.spam_flag_cleared', $complaint));
    }

    public function test_the_admin_can_release_a_spam_flag(): void
    {
        $complaint = $this->flaggedComplaint('spam');

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'A real report, not promotional — verified in person on 13 Nov.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint));

        $this->assertSame('clear', $complaint->fresh()->spam_status);
    }

    public function test_an_unflagged_report_cannot_be_released(): void
    {
        $complaint = $this->flaggedComplaint('clear');

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'Not flagged anymore — this call should refuse.',
            ])
            ->assertSessionHas('error');

        $this->assertSame('clear', $complaint->fresh()->spam_status);
    }

    public function test_a_reason_is_required_to_release(): void
    {
        $complaint = $this->flaggedComplaint('spam');

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [])
            ->assertSessionHasErrors('clear_flag_reason');

        $this->assertSame('spam', $complaint->fresh()->spam_status);
    }

    public function test_staff_cannot_clear_the_flag(): void
    {
        $complaint = $this->flaggedComplaint('spam');

        $this->actingAs($this->staff)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'Staff should not be able to overrule the automated flag.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('spam', $complaint->fresh()->spam_status);
    }

    public function test_a_released_verified_public_report_reappears_on_the_register(): void
    {
        $complaint = $this->flaggedComplaint('spam');

        $this->assertFalse(Complaint::query()->public()->whereKey($complaint->id)->exists());

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'Reviewed on site — the pothole is real and the report is legitimate.',
            ]);

        $this->assertTrue(Complaint::query()->public()->whereKey($complaint->fresh()->id)->exists());
    }

    public function test_the_release_reason_lands_in_the_audit_trail_with_the_original_flag(): void
    {
        $complaint = $this->flaggedComplaint('spam', spamScore: 88);

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.clear-spam-flag', $complaint), [
                'clear_flag_reason' => 'Walked the route and confirmed the damage is real.',
            ]);

        $log = ActivityLog::where('action', 'complaint.spam_flag_cleared')
            ->where('subject_id', $complaint->id)
            ->sole();

        $this->assertSame('spam', $log->metadata['previous_spam_status']);
        $this->assertSame('Walked the route and confirmed the damage is real.', $log->metadata['reason']);
    }

    private function flaggedComplaint(string $flag, int $spamScore = 40): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'category' => 'road_damage',
            'status' => ComplaintStatus::UnderReview->value,
            'review_status' => 'verified',
            'verified_at' => now(),
            'is_public' => true,
            'spam_status' => $flag,
            'spam_score' => $spamScore,
            'spam_reasons' => $flag === 'clear' ? null : ['Contains promotional or spam language'],
        ]);
    }

    private function auditHas(string $action, Complaint $complaint): bool
    {
        return ActivityLog::where('action', $action)
            ->where('subject_type', Complaint::class)
            ->where('subject_id', $complaint->id)
            ->exists();
    }
}
