<?php

namespace Tests\Feature;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
    }

    public function test_resident_submission_has_no_urgency_input_and_starts_pending_with_a_suggestion(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertDontSee('name="urgency"', false)
            ->assertDontSee('Urgency Level', false);

        $response = $this->actingAs($resident)->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Dangerous blocked road report',
            'description' => 'A dangerous blocked road is preventing residents from reaching the health center safely.',
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $complaint = Complaint::latest('id')->firstOrFail();

        $this->assertSame(ComplaintReviewStatus::Pending->value, $complaint->review_status);
        $this->assertNull($complaint->confirmed_priority);
        $this->assertFalse($complaint->is_public);
        $this->assertSame(ComplaintStatus::Submitted->value, $complaint->status);
        $this->assertContains($complaint->suggested_priority, [
            ComplaintPriority::Urgent->value,
            ComplaintPriority::Critical->value,
        ]);
        $this->assertNotEmpty($complaint->suggestion_reasons);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.filed',
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_photo_evidence_is_required_for_every_new_complaint(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)
            ->post(route('complaints.store'), [
                'category' => 'road_damage',
                'title' => 'Missing evidence report',
                'description' => 'This report does not include the required photo evidence.',
                'terms' => '1',
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_staff_sees_only_a_resident_alias_while_admin_can_see_real_identity(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'RealFirst',
            'last_name' => 'RealLast',
            'email' => 'real.resident@example.test',
            'contact_number' => '09171234567',
            'display_alias' => 'Masked neighbor',
        ]);
        $complaint = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Masked neighbor')
            ->assertSee('Identity hidden from staff')
            ->assertDontSee('RealFirst')
            ->assertDontSee('real.resident@example.test')
            ->assertDontSee('09171234567');

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('RealFirst RealLast')
            ->assertSee('real.resident@example.test')
            ->assertSee('09171234567');
    }

    public function test_staff_review_requires_a_confirmed_priority_before_verification(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        $this->actingAs($staff)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => ComplaintReviewStatus::Verified->value,
                'review_notes' => 'The report is credible, but priority is still missing.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(ComplaintReviewStatus::Pending->value, $complaint->fresh()->review_status);
        $this->assertNull($complaint->fresh()->confirmed_priority);
    }

    public function test_staff_can_verify_confirm_priority_and_opt_in_to_public_visibility(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        $this->actingAs($staff)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => ComplaintReviewStatus::Verified->value,
                'confirmed_priority' => ComplaintPriority::Urgent->value,
                'review_notes' => 'Location and report details were checked.',
                'is_public' => '1',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('status');

        $complaint->refresh();

        $this->assertSame(ComplaintReviewStatus::Verified->value, $complaint->review_status);
        $this->assertSame(ComplaintPriority::Urgent->value, $complaint->confirmed_priority);
        $this->assertTrue($complaint->is_public);
        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->status);
        $this->assertDatabaseHas('complaint_logs', [
            'complaint_id' => $complaint->id,
            'actor_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.reviewed',
            'subject_id' => $complaint->id,
        ]);
        $this->assertTrue(Complaint::public()->whereKey($complaint->id)->exists());
    }

    public function test_pending_complaint_cannot_advance_before_review(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'status' => ComplaintStatus::Submitted->value,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        $this->actingAs($staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'Attempting to move the report before verification.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(ComplaintStatus::Submitted->value, $complaint->fresh()->status);
    }

    public function test_unverified_public_records_are_excluded_from_public_scope(): void
    {
        $complaint = Complaint::factory()->create([
            'is_public' => true,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
        ]);

        $this->assertFalse(Complaint::public()->whereKey($complaint->id)->exists());
        $this->assertTrue($complaint->fresh()->is_public);
    }

    public function test_admin_cannot_publish_a_pending_complaint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = Complaint::factory()->create([
            'status' => ComplaintStatus::Submitted->value,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Submitted->value,
                'review_status' => ComplaintReviewStatus::Pending->value,
                'is_public' => '1',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($complaint->fresh()->is_public);
    }
}
