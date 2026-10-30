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
            ->assertSee('name="images[]"', false)
            ->assertSee('capture="environment"', false)
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
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_resident_can_attach_up_to_five_photos_and_all_paths_are_recorded(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $response = $this->actingAs($resident)->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Multiple evidence photos',
            'description' => 'Several clear photos show the damage and the area around the reported issue.',
            'images' => $this->complaintEvidenceSet(5),
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $complaint = Complaint::latest('id')->firstOrFail();

        $this->assertCount(5, $complaint->image_paths);
        $this->assertSame($complaint->image_paths[0], $complaint->image_path);
        $this->assertCount(5, Storage::disk('minio')->allFiles('complaints'));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.filed',
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_camera_photo_is_accepted_as_evidence(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)->post(route('complaints.store'), [
            'category' => 'flooding',
            'title' => 'Photo taken at the flood site',
            'description' => 'The attached camera photo shows the flooded roadway near the community entrance.',
            'camera_photo' => $this->complaintEvidence(),
            'terms' => '1',
        ])->assertRedirect();

        $complaint = Complaint::latest('id')->firstOrFail();

        $this->assertCount(1, $complaint->image_paths);
        $this->assertSame($complaint->image_path, $complaint->image_paths[0]);
    }

    public function test_more_than_five_photos_are_rejected(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($resident)->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Too many evidence photos',
            'description' => 'This report includes more photos than the allowed evidence limit for one complaint.',
            'images' => $this->complaintEvidenceSet(6),
            'terms' => '1',
        ])->assertSessionHasErrors('images');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_evidence_is_streamed_only_to_authorized_users(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $otherResident = User::factory()->create(['role' => 'citizen']);
        $complaint = Complaint::factory()->create([
            'user_id' => $owner->id,
            'image_path' => 'complaints/evidence.png',
            'image_paths' => ['complaints/evidence.png'],
        ]);
        Storage::disk('minio')->put('complaints/evidence.png', 'private-image-bytes');

        $this->actingAs($owner)
            ->get(route('complaints.evidence', [$complaint, 0]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'max-age=3600, private');

        $this->actingAs($otherResident)
            ->get(route('complaints.evidence', [$complaint, 0]))
            ->assertForbidden();
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
            'can_verify' => true,
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

    public function test_staff_review_cannot_change_public_visibility(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        // The review form used to carry an "allow public visibility" checkbox.
        // Submitting it must now be inert: the field is neither validated nor
        // written, so a reviewer who sends it cannot publish a private
        // complaint even by accident or from a stale bookmarked form.
        $this->actingAs($staff)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => ComplaintReviewStatus::Verified->value,
                'confirmed_priority' => ComplaintPriority::Urgent->value,
                'review_notes' => 'Location and report details were checked.',
                'is_public' => '1',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('status')
            ->assertSessionMissing('errors');

        $complaint->refresh();

        // The review itself lands.
        $this->assertSame(ComplaintReviewStatus::Verified->value, $complaint->review_status);
        $this->assertSame(ComplaintPriority::Urgent->value, $complaint->confirmed_priority);
        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->status);

        // The consent does not.
        $this->assertFalse(
            $complaint->is_public,
            'A reviewer must not be able to publish a complaint the resident kept private.',
        );
    }

    public function test_rejecting_a_complaint_leaves_the_residents_visibility_choice_alone(): void
    {
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'can_verify' => true,
        ]);

        $public = Complaint::factory()->create([
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => true,
        ]);
        $private = Complaint::factory()->create([
            'department_id' => $department->id,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        foreach ([$public, $private] as $complaint) {
            $this->actingAs($staff)
                ->put(route('staff.complaints.review', $complaint), [
                    'review_status' => ComplaintReviewStatus::Rejected->value,
                    'review_notes' => 'Could not be located.',
                ])
                ->assertRedirect(route('staff.complaints.show', $complaint));
        }

        // Rejection withdraws the confirmed priority, which is a review fact.
        $this->assertNull($public->fresh()->confirmed_priority);
        $this->assertNull($private->fresh()->confirmed_priority);

        // It must not withdraw consent, in either direction. Rejecting used to
        // force is_public to false, which let an office un-publish a resident
        // simply by deciding against their complaint.
        $this->assertTrue($public->fresh()->is_public);
        $this->assertFalse($private->fresh()->is_public);
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

    public function test_a_residents_consent_is_enough_to_publish_before_verification(): void
    {
        // Verification answers "is this report accurate", not "did this resident
        // agree to be public". The two are now independent, so a complaint the
        // resident published is listed even while it is still awaiting review.
        $complaint = Complaint::factory()->create([
            'is_public' => true,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
        ]);

        $this->assertTrue(Complaint::public()->whereKey($complaint->id)->exists());
        $this->assertTrue($complaint->fresh()->isPubliclyVisible());
    }

    public function test_a_private_complaint_is_never_public_regardless_of_review(): void
    {
        $verified = Complaint::factory()->create([
            'is_public' => false,
            'review_status' => ComplaintReviewStatus::Verified->value,
            'confirmed_priority' => 'routine',
        ]);

        $this->assertFalse(Complaint::public()->whereKey($verified->id)->exists());
        $this->assertFalse($verified->fresh()->isPubliclyVisible());
    }

    public function test_an_admin_cannot_publish_a_complaint_the_resident_kept_private(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = Complaint::factory()->create([
            'status' => ComplaintStatus::Submitted->value,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'confirmed_priority' => null,
            'is_public' => false,
        ]);

        // is_public is absent from the admin update payload entirely, so the
        // request is neither rejected nor honoured — it is simply ignored. An
        // admin has moderation powers over content but no authority to consent
        // on a resident's behalf.
        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Submitted->value,
                'review_status' => ComplaintReviewStatus::Pending->value,
                'is_public' => '1',
            ])
            ->assertSessionMissing('errors');

        $this->assertFalse(
            $complaint->fresh()->is_public,
            'An admin must not be able to publish a private complaint.',
        );
    }
}
