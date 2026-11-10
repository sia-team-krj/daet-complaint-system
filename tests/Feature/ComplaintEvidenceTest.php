<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every photograph of the work gets a row of its own, and the row says where
 * and when the photograph was taken.
 *
 * The place is the point. A photograph of a repair only means something if the
 * location it was taken at is on the record — otherwise it is a picture of
 * anything, taken anywhere, at any time.
 */
class ComplaintEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

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
            'can_verify' => true,
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_a_documentation_photo_gets_a_row_of_its_own(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(2),
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $log = ComplaintLog::where('complaint_id', $complaint->id)
            ->where('new_status', ComplaintStatus::InProgress->value)
            ->sole();

        $this->assertCount(2, $log->evidences);
        $this->assertCount(2, $log->documentation_evidence);
        $this->assertSame(
            $log->documentation_images,
            collect($log->documentation_evidence)->pluck('path')->all(),
            'The evidence rows must line up with the path list by index.',
        );
    }

    public function test_the_row_records_who_attached_the_photograph(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ]);

        $evidence = ComplaintEvidence::sole();

        $this->assertSame($this->staff->id, $evidence->uploaded_by);
        $this->assertSame($this->staff->id, $evidence->uploader->id);
    }

    public function test_the_row_says_which_part_of_the_work_it_proves(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ]);

        $this->assertSame('work_start', ComplaintEvidence::sole()->stage);
    }

    public function test_a_photograph_with_gps_records_where_it_was_taken(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => [$this->proofPhotoWithGps(14.12345678, 121.65432100)],
            ]);

        $evidence = ComplaintEvidence::sole();

        $this->assertNotNull($evidence->latitude);
        $this->assertEqualsWithDelta(14.12345678, $evidence->latitude, 0.000001);
        $this->assertEqualsWithDelta(121.65432100, $evidence->longitude, 0.000001);
        $this->assertSame('exif', $evidence->location_source);
        $this->assertTrue($evidence->hasLocation());
    }

    public function test_a_photograph_without_gps_says_so_rather_than_guessing(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ]);

        $evidence = ComplaintEvidence::sole();

        $this->assertNull($evidence->latitude);
        $this->assertNull($evidence->longitude);
        $this->assertSame('none', $evidence->location_source);
        $this->assertFalse($evidence->hasLocation());
    }

    public function test_the_staff_timeline_shows_the_proof_and_its_location(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => [$this->proofPhotoWithGps(14.12345678, 121.65432100)],
            ]);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('proof-timeline__location', false)
            ->assertSee('14.123457, 121.654321');
    }

    public function test_the_staff_timeline_says_when_a_photograph_had_no_location(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ]);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('No location recorded');
    }

    public function test_a_failed_update_leaves_no_orphaned_photographs(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        // No photograph attached, so the update is refused — and nothing was
        // written to storage in the first place.
        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $this->assertSame(0, count(Storage::disk('minio')->allFiles()));
        $this->assertDatabaseCount('complaint_evidences', 0);
    }

    private function complaint(ComplaintStatus $status): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'road_damage',
            'status' => $status->value,
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);
    }
}
