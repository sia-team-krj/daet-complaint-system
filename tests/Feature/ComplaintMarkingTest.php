<?php

namespace Tests\Feature;

use App\Enums\ComplaintMarking;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Office staff mark a report as misleading or spam — the office's own
 * rejection of the report. The field officer always writes a description
 * and a comment; the Inspector (Department Administrator) may mark without
 * them. Office management is notified for review.
 */
class ComplaintMarkingTest extends TestCase
{
    use RefreshDatabase;

    private User $resident;
    private User $fieldOfficer;
    private User $inspector;
    private User $otherOfficer;
    private User $mayorOfficeStaff;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->fieldOfficer = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('ENG')->id,
        ]);
        $this->inspector = User::factory()->create([
            'role' => 'department_admin',
            'department_id' => $this->department('ENG')->id,
        ]);
        $this->otherOfficer = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('MENRO')->id,
        ]);
        $this->mayorOfficeStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('OM')->id,
        ]);
        $this->superAdmin = User::factory()->create(['role' => 'admin']);
    }

    public function test_a_field_officer_can_mark_a_report_as_misleading(): void
    {
        $complaint = $this->complaint();

        $this->mark($complaint);

        $complaint->refresh();
        $this->assertTrue($complaint->isMarked());
        $this->assertSame(ComplaintMarking::Misleading, $complaint->markingEnum());
        $this->assertSame('The pothole was already repaired last month.', $complaint->mark_description);
        $this->assertSame('The photo shows a different road entirely.', $complaint->mark_comment);
        $this->assertSame($this->fieldOfficer->id, $complaint->marked_by);
        $this->assertNotNull($complaint->marked_at);
        $this->assertSame(ComplaintStatus::Rejected->value, $complaint->status);
    }

    public function test_a_field_officer_must_give_a_description_and_a_comment(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->fieldOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Spam->value,
                'description' => '',
                'comment' => '',
            ])
            ->assertSessionHasErrors(['description', 'comment']);

        $complaint->refresh();
        $this->assertFalse($complaint->isMarked());
        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->status);
    }

    public function test_an_inspector_can_mark_without_a_description_and_comment(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->inspector)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Spam->value,
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHasNoErrors();

        $complaint->refresh();
        $this->assertSame(ComplaintMarking::Spam, $complaint->markingEnum());
        $this->assertNull($complaint->mark_description);
        $this->assertNull($complaint->mark_comment);
        $this->assertSame(ComplaintStatus::Rejected->value, $complaint->status);
    }

    public function test_a_marking_choice_is_required(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->fieldOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => '',
                'description' => 'The pothole was already repaired last month.',
                'comment' => 'The photo shows a different road entirely.',
            ])
            ->assertSessionHasErrors('marking');

        $this->assertFalse($complaint->fresh()->isMarked());
    }

    public function test_the_office_of_the_mayor_and_the_super_admin_are_notified(): void
    {
        $complaint = $this->complaint();

        $this->mark($complaint);

        foreach ([$this->mayorOfficeStaff, $this->superAdmin] as $recipient) {
            $this->assertSame(1, Notification::query()
                ->where('user_id', $recipient->id)
                ->where('type', Notification::COMPLAINT_MARKED_MISLEADING_OR_SPAM)
                ->count());
        }

        // The field officer of the handling office, and a verifier of another
        // office, are not the management route this notification is for.
        foreach ([$this->fieldOfficer, $this->otherOfficer] as $notRecipient) {
            $this->assertSame(0, Notification::query()
                ->where('user_id', $notRecipient->id)
                ->where('type', Notification::COMPLAINT_MARKED_MISLEADING_OR_SPAM)
                ->count());
        }
    }

    public function test_the_marking_and_the_comment_are_on_the_immutable_timeline(): void
    {
        $complaint = $this->complaint();

        $this->mark($complaint);

        $log = ComplaintLog::query()
            ->where('complaint_id', $complaint->id)
            ->where('new_status', ComplaintStatus::Rejected->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(ComplaintStatus::InProgress->value, $log->previous_status);
        $this->assertStringContainsString('Misleading', $log->comment);
        $this->assertStringContainsString('different road', $log->comment);
    }

    public function test_a_report_cannot_be_marked_twice(): void
    {
        $complaint = $this->complaint();
        $this->mark($complaint);

        $this->actingAs($this->fieldOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Spam->value,
                'description' => 'A different reason.',
                'comment' => 'A different comment.',
            ])
            ->assertSessionHasErrors('marking');

        $this->assertSame(ComplaintMarking::Misleading, $complaint->fresh()->markingEnum());
    }

    public function test_a_report_already_rejected_by_a_verifier_cannot_be_marked(): void
    {
        $complaint = $this->complaint();
        $complaint->update(['status' => ComplaintStatus::Rejected->value]);

        $this->actingAs($this->fieldOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Misleading->value,
                'description' => 'The pothole was already repaired last month.',
                'comment' => 'The photo shows a different road entirely.',
            ])
            ->assertSessionHasErrors('marking');

        $this->assertFalse($complaint->fresh()->isMarked());
    }

    public function test_an_office_cannot_mark_a_report_outside_its_own(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->otherOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Misleading->value,
                'description' => 'The pothole was already repaired last month.',
                'comment' => 'The photo shows a different road entirely.',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($complaint->fresh()->isMarked());
    }

    public function test_a_global_administrator_cannot_use_the_office_marking(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->superAdmin)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), [
                'marking' => ComplaintMarking::Misleading->value,
                'description' => 'The pothole was already repaired last month.',
                'comment' => 'The photo shows a different road entirely.',
            ])
            ->assertSessionHasErrors('marking');

        $this->assertFalse($complaint->fresh()->isMarked());
    }

    public function test_the_marking_is_visible_on_the_ticket_after_rejection(): void
    {
        $complaint = $this->complaint();
        $this->mark($complaint);

        $this->actingAs($this->fieldOfficer)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Marked as misleading or spam');
    }

    public function test_the_form_is_shown_while_the_report_is_open(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->fieldOfficer)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Mark this report as misleading or spam')
            ->assertSee(route('staff.complaints.mark-misleading-spam', $complaint), false);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function mark(Complaint $complaint, array $overrides = []): void
    {
        $this->actingAs($this->fieldOfficer)
            ->post(route('staff.complaints.mark-misleading-spam', $complaint), array_merge([
                'marking' => ComplaintMarking::Misleading->value,
                'description' => 'The pothole was already repaired last month.',
                'comment' => 'The photo shows a different road entirely.',
            ], $overrides))
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHasNoErrors();
    }

    private function department(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    private function complaint(): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $this->resident->id,
            'department_id' => $this->department('ENG')->id,
            'assigned_staff_id' => $this->fieldOfficer->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => ComplaintStatus::InProgress->value,
            'review_status' => 'verified',
            'verified_at' => now()->subDay(),
            'is_public' => true,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
