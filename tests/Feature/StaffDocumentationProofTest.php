<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffDocumentationProofTest extends TestCase
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
        ]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_staff_response_form_exposes_gallery_camera_and_preview_controls(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Photo library')
            ->assertSee('Use camera')
            ->assertSee('documentation-previews', false)
            ->assertSee('name="documentation_camera_photos[]"', false)
            ->assertSee('data-documentation-trigger="documentation_camera_photos"', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('multiple', false);
    }

    public function test_staff_cannot_mark_a_complaint_in_progress_without_documentation(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The field team has started work on the reported issue.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->fresh()->status);
        $this->assertDatabaseCount('complaint_logs', 0);
    }

    public function test_staff_can_mark_in_progress_with_documentation_and_audit_the_proof(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The field team has started work on the reported issue.',
                'documentation_images' => $this->complaintEvidenceSet(2),
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();
        $log = ComplaintLog::where('complaint_id', $complaint->id)
            ->where('new_status', ComplaintStatus::InProgress->value)
            ->sole();

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->status);
        $this->assertCount(2, $log->documentation_images);
        $this->assertCount(2, Storage::disk('minio')->allFiles('complaint-updates/'.$complaint->id));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.status_updated',
            'subject_id' => $complaint->id,
        ]);
        $activity = ActivityLog::where('action', 'complaint.status_updated')
            ->where('subject_id', $complaint->id)
            ->sole();
        $this->assertSame(2, $activity->metadata['documentation_count']);
    }

    public function test_staff_can_resolve_only_with_documentation_proof(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::InProgress);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'note' => 'The repair is complete and the site has been cleared.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'note' => 'The repair is complete and the site has been cleared.',
                'documentation_camera_photos' => $this->complaintEvidenceSet(2),
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::Resolved->value, $complaint->fresh()->status);
        $this->assertCount(2, ComplaintLog::where('complaint_id', $complaint->id)
            ->where('new_status', ComplaintStatus::Resolved->value)
            ->sole()
            ->documentation_images);
    }

    public function test_documentation_is_optional_for_a_non_processing_transition(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::Submitted);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'The department has accepted the report for review.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->fresh()->status);
    }

    public function test_public_documentation_is_visible_to_the_complaint_owner_but_not_another_resident(): void
    {
        $complaint = $this->verifiedComplaint(ComplaintStatus::UnderReview);
        $log = ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->staff->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'Work started with documented field work.',
            'documentation_paths' => ['complaint-updates/'.$complaint->id.'/proof.png'],
            'is_internal' => false,
        ]);
        Storage::disk('minio')->put('complaint-updates/'.$complaint->id.'/proof.png', 'proof-bytes');

        $this->actingAs($this->resident)
            ->get(route('complaints.updates.evidence', [$complaint, $log, 0]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $otherResident = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($otherResident)
            ->get(route('complaints.updates.evidence', [$complaint, $log, 0]))
            ->assertForbidden();

        $internalLog = ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->staff->id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'Internal documentation note.',
            'documentation_paths' => ['complaint-updates/'.$complaint->id.'/internal.png'],
            'is_internal' => true,
        ]);
        Storage::disk('minio')->put('complaint-updates/'.$complaint->id.'/internal.png', 'internal-bytes');

        $this->actingAs($this->resident)
            ->get(route('complaints.updates.evidence', [$complaint, $internalLog, 0]))
            ->assertForbidden();
    }

    private function verifiedComplaint(ComplaintStatus $status): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'status' => $status->value,
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);
    }
}
