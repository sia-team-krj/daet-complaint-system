<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffIdentityMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

    private User $admin;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);

        $this->resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Revealed',
            'last_name' => 'Resident',
            'display_alias' => 'Alias-Resident-42',
            'email' => 'revealed-resident@example.test',
            'contact_number' => '09171234567',
        ]);
    }

    public function test_staff_complaint_detail_masks_resident_identity(): void
    {
        $complaint = $this->complaint();

        $response = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee($this->resident->display_alias);
        $response->assertDontSee($this->resident->full_name);
        $response->assertDontSee($this->resident->email);
        $response->assertDontSee($this->resident->contact_number);
    }

    public function test_staff_timeline_masks_a_resident_actor(): void
    {
        $complaint = $this->complaint();
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::UnderReview->value,
            'comment' => 'Resident provided additional context.',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint->fresh()))
            ->assertOk();

        $response->assertSee($this->resident->display_alias);
        $response->assertDontSee($this->resident->full_name);
        $response->assertDontSee($this->resident->email);
    }

    public function test_live_ticket_state_masks_a_resident_actor(): void
    {
        $complaint = $this->complaint();

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::UnderReview->value,
            'comment' => 'Resident replied.',
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson(route('staff.complaints.state', $complaint))
            ->assertOk();

        $response->assertJsonPath('actor', $this->resident->display_alias);
        $this->assertStringContainsString($this->resident->display_alias, (string) $response->json('message'));
        $this->assertStringNotContainsString($this->resident->full_name, (string) $response->json('message'));
        $this->assertStringNotContainsString($this->resident->email, json_encode($response->json()));
    }

    public function test_admin_complaint_detail_shows_resident_real_identity(): void
    {
        $complaint = $this->complaint();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee($this->resident->full_name);
    }

    private function complaint(): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'road_damage',
            'status' => ComplaintStatus::UnderReview->value,
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
            'verified_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
        ]);
    }
}
