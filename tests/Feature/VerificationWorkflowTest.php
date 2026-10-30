<?php

namespace Tests\Feature;

use App\Enums\InformationRequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
    }

    public function test_verifier_is_a_capability_not_a_role(): void
    {
        $regular = $this->staff(canVerify: false);
        $verifier = $this->staff(canVerify: true);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $this->assertFalse($regular->canVerifyComplaints());
        $this->assertTrue($verifier->canVerifyComplaints());

        // Admin is the fallback so a department never freezes when its single
        // verifier is unavailable.
        $this->assertTrue($admin->canVerifyComplaints());

        // The flag must not change anyone's role or department scope.
        $this->assertSame('staff', $verifier->role);
        $this->assertSame($this->department->id, $verifier->department_id);
    }

    public function test_complaint_tracks_the_verifier_separately_from_the_worker(): void
    {
        $verifier = $this->staff(canVerify: true);
        $worker = $this->staff();

        $complaint = $this->complaint();
        $complaint->update([
            'verifying_staff_id' => $verifier->id,
            'assigned_staff_id' => $worker->id,
        ]);

        $complaint->refresh();

        $this->assertTrue($verifier->is($complaint->verifyingStaff));
        $this->assertTrue($worker->is($complaint->assignedStaff));
        $this->assertFalse($complaint->hasSelfVerificationConflict());
    }

    public function test_self_verification_conflict_is_detected(): void
    {
        $person = $this->staff(canVerify: true);

        $complaint = $this->complaint();
        $complaint->update([
            'verifying_staff_id' => $person->id,
            'assigned_staff_id' => $person->id,
        ]);

        $this->assertTrue($complaint->fresh()->hasSelfVerificationConflict());
    }

    public function test_no_conflict_when_only_one_seat_is_filled(): void
    {
        $person = $this->staff(canVerify: true);

        $verifyingOnly = $this->complaint();
        $verifyingOnly->update(['verifying_staff_id' => $person->id]);
        $this->assertFalse($verifyingOnly->fresh()->hasSelfVerificationConflict());

        $assignedOnly = $this->complaint();
        $assignedOnly->update(['assigned_staff_id' => $person->id]);
        $this->assertFalse($assignedOnly->fresh()->hasSelfVerificationConflict());
    }

    public function test_verification_method_distinguishes_review_from_attendance(): void
    {
        $desk = VerificationMethod::Desk;
        $site = VerificationMethod::SiteVisit;
        $duplicate = VerificationMethod::DuplicateMatch;

        // The public register must be able to say which of these happened.
        $this->assertNotSame($desk->label(), $site->label());
        $this->assertSame('Report reviewed', $desk->label());
        $this->assertSame('Site attended', $site->label());
        $this->assertSame('Already reported', $duplicate->label());

        $this->assertTrue($site->requiresSiteAttendance());
        $this->assertFalse($desk->requiresSiteAttendance());
        $this->assertFalse($duplicate->requiresSiteAttendance());
    }

    public function test_verification_method_is_cast_to_the_enum(): void
    {
        $complaint = $this->complaint();
        $complaint->update(['verification_method' => VerificationMethod::SiteVisit->value]);

        $this->assertInstanceOf(VerificationMethod::class, $complaint->fresh()->verification_method);
        $this->assertTrue($complaint->fresh()->verification_method->requiresSiteAttendance());
    }

    public function test_information_request_records_the_question_and_the_reply(): void
    {
        $verifier = $this->staff(canVerify: true);
        $complaint = $this->complaint();

        $request = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'The streetlight photo is too dark to read the pole number. Could you send a closer shot?',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->assertTrue($request->isOpen());
        $this->assertTrue($verifier->is($request->requester));
        $this->assertTrue($complaint->is($request->complaint));

        $request->update([
            'status' => InformationRequestStatus::Answered->value,
            'response_text' => 'Here is a closer shot with the pole number visible.',
            'responded_at' => now(),
        ]);

        $request->refresh();

        $this->assertTrue($request->isAnswered());
        $this->assertNotNull($request->responded_at);
        $this->assertNull($request->resolved_at);
    }

    public function test_outstanding_request_is_the_one_still_needing_attention(): void
    {
        $verifier = $this->staff(canVerify: true);
        $complaint = $this->complaint();

        $closed = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'First ask.',
            'status' => InformationRequestStatus::Resolved->value,
            'round' => 1,
        ]);

        $this->assertNull($complaint->outstandingInformationRequest());

        $open = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'Second ask.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 2,
        ]);

        $outstanding = $complaint->fresh()->outstandingInformationRequest();

        $this->assertNotNull($outstanding);
        $this->assertTrue($open->is($outstanding));
        $this->assertFalse($closed->is($outstanding));
    }

    public function test_stale_request_is_visible_so_it_cannot_rot_silently(): void
    {
        $verifier = $this->staff(canVerify: true);
        $complaint = $this->complaint();

        $fresh = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'Just asked.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->assertFalse($fresh->isStale(days: 3));

        $old = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'Asked a while ago.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 2,
        ]);
        $old->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->assertTrue($old->fresh()->isStale(days: 3));

        // A closed request is never stale, however old it is.
        $closed = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'Old but closed.',
            'status' => InformationRequestStatus::Resolved->value,
            'round' => 3,
        ]);
        $closed->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->assertFalse($closed->fresh()->isStale(days: 3));
    }

    public function test_admin_and_staff_queues_share_one_pagination_view(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $staff = $this->staff();

        $adminHtml = $this->actingAs($admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->getContent();

        $staffHtml = $this->actingAs($staff)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->getContent();

        // Two identical hand-rolled pagers is exactly the drift this prevents.
        foreach (['admin' => $adminHtml, 'staff' => $staffHtml] as $surface => $html) {
            $this->assertStringNotContainsString(
                'rel="next"',
                $html,
                "The {$surface} surface should not fall back to Laravel's default pagination markup.",
            );
        }

        $this->assertFileExists(resource_path('views/vendor/pagination/workspace.blade.php'));
        $this->assertFileDoesNotExist(
            resource_path('views/vendor/pagination/admin.blade.php'),
            'The admin-only pager should have been folded into the shared workspace pager.',
        );
    }

    private function staff(bool $canVerify = false): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => $canVerify,
        ]);
    }

    private function complaint(): Complaint
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $citizen->id,
            'department_id' => $this->department->id,
            'category' => 'Road Damage',
            'title' => 'Fallen tree blocking the road',
            'description' => 'A large tree has fallen across the road near the barangel hall.',
            'address_text' => 'Near the barangay hall',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'is_public' => false,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
