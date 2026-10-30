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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Department $otherDepartment;

    private User $verifier;

    private User $regularStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->otherDepartment = Department::where('code', 'GSO')->firstOrFail();

        $this->verifier = $this->staff(canVerify: true);
        $this->regularStaff = $this->staff();
    }

    public function test_a_verifier_can_verify_a_complaint_in_their_department(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'urgent',
                'verification_method' => 'site_visit',
            ])
            ->assertRedirect();

        $complaint->refresh();

        $this->assertSame('verified', $complaint->review_status);
        $this->assertSame('urgent', $complaint->confirmed_priority);
        $this->assertInstanceOf(VerificationMethod::class, $complaint->verification_method);
        $this->assertSame(VerificationMethod::SiteVisit, $complaint->verification_method);
        $this->assertTrue($complaint->verification_method->requiresSiteAttendance());
        $this->assertTrue($this->verifier->is($complaint->verifyingStaff));
        $this->assertSame('Under Review', $complaint->status, 'Verifying moves a submitted complaint under review.');
    }

    public function test_regular_staff_cannot_verify(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->regularStaff)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertSessionHasErrors('verification');

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_a_verifier_cannot_verify_another_departments_complaint(): void
    {
        $complaint = $this->complaint(department: $this->otherDepartment);

        // Cross-department access is a hard 403, not a form error.
        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_verification_requires_a_confirmed_priority(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
            ])
            ->assertSessionHasErrors('confirmed_priority');

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_a_verifier_cannot_publish_a_complaint_through_the_review_action(): void
    {
        $complaint = $this->complaint();

        // There used to be a rule here — "only verified complaints can be marked
        // public" — enforced by rejecting the review outright. Publication is no
        // longer part of a review decision at all, so the field is not validated
        // and the review proceeds normally while the flag is ignored.
        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'rejected',
                'review_notes' => 'Location could not be confirmed.',
                'is_public' => true,
            ])
            ->assertSessionMissing('errors');

        $this->assertFalse(
            $complaint->fresh()->is_public,
            'A verifier must not be able to publish a complaint through review.',
        );
    }

    public function test_rejecting_clears_priority_but_not_the_consent(): void
    {
        $complaint = $this->complaint();
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'urgent',
            'is_public' => true,
        ]);

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'rejected',
                'review_notes' => 'Location could not be confirmed.',
            ])
            ->assertSessionMissing('errors');

        $complaint->refresh();

        // The review fact is withdrawn.
        $this->assertSame('rejected', $complaint->review_status);
        $this->assertNull(
            $complaint->confirmed_priority,
            'Rejection must clear the confirmed priority.',
        );

        // The resident's decision is not theirs to withdraw on this path.
        $this->assertTrue(
            $complaint->is_public,
            'Rejection must not un-publish a complaint the resident published.',
        );
    }
    public function test_rejecting_a_public_complaint_must_be_confirmed_not_silently_unpublished(): void
    {
        $complaint = $this->complaint();
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'urgent',
            'is_public' => true,
        ]);

        // Unpublishing a public register entry without saying so would be a
        // transparency problem, so the verifier is asked to confirm it.
        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'rejected',
                'is_public' => '0',
            ])
            // No confirmation dialog is needed any more, because the review
            // action has no publication power to withdraw in the first place.
            ->assertSessionMissing('errors');

        $complaint->refresh();

        $this->assertSame('rejected', $complaint->review_status);
        $this->assertTrue(
            $complaint->is_public,
            'Nothing may be silently unpublished, and rejection cannot un-publish.',
        );
    }

    public function test_the_assigned_worker_cannot_verify_their_own_assignment(): void
    {
        $complaint = $this->complaint();
        $complaint->update(['assigned_staff_id' => $this->verifier->id]);

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertSessionHasErrors('verification');

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_verification_can_be_delegated_within_the_department(): void
    {
        $secondVerifier = $this->staff(canVerify: true);
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.delegate', $complaint), [
                'verifying_staff_id' => $secondVerifier->id,
            ])
            ->assertRedirect();

        $this->assertTrue($secondVerifier->is($complaint->fresh()->verifyingStaff));
    }

    public function test_verification_cannot_be_delegated_to_the_assigned_worker(): void
    {
        $complaint = $this->complaint();
        $complaint->update(['assigned_staff_id' => $this->regularStaff->id]);

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.delegate', $complaint), [
                'verifying_staff_id' => $this->regularStaff->id,
            ])
            ->assertSessionHasErrors('verification');
    }

    public function test_verification_cannot_be_delegated_to_a_non_verifier(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.delegate', $complaint), [
                'verifying_staff_id' => $this->regularStaff->id,
            ])
            ->assertSessionHasErrors('verifying_staff_id');
    }

    public function test_verification_cannot_be_delegated_outside_the_department(): void
    {
        $outsider = $this->staff(canVerify: true, department: $this->otherDepartment);
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.delegate', $complaint), [
                'verifying_staff_id' => $outsider->id,
            ])
            ->assertSessionHasErrors('verifying_staff_id');
    }

    public function test_verifier_can_ask_the_resident_for_more_evidence(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'The streetlight photo is too dark to read the pole number. Please send a closer shot.',
            ])
            ->assertRedirect();

        $complaint->refresh();

        $this->assertSame('needs_information', $complaint->review_status);
        $this->assertFalse($complaint->is_public);

        $request = $complaint->outstandingInformationRequest();

        $this->assertNotNull($request);
        $this->assertTrue($request->isOpen());
        $this->assertSame(1, $request->round);
        $this->assertTrue($this->verifier->is($request->requester));
    }

    public function test_a_vague_information_request_is_refused(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'more',
            ])
            ->assertSessionHasErrors('question');

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_only_one_information_request_may_be_open_at_a_time(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'Please send a photo that clearly shows the damaged signage.',
            ]);

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'Also please resend the original photo from a different angle.',
            ])
            ->assertSessionHasErrors('question');

        $this->assertSame(1, ComplaintInformationRequest::count());
    }

    public function test_information_requests_are_capped_then_escalated(): void
    {
        $complaint = $this->complaint();

        for ($round = 1; $round <= 2; $round++) {
            $this->actingAs($this->verifier)
                ->post(route('staff.complaints.verification.request-information', $complaint), [
                    'question' => "Round {$round}: please attach a wider photo of the whole structure.",
                ])
                ->assertRedirect();

            // Close the round so the next one is allowed to open.
            $complaint->informationRequests()->outstanding()->update([
                'status' => InformationRequestStatus::Resolved->value,
                'resolved_at' => now(),
            ]);
        }

        $this->assertSame(2, $complaint->informationRequests()->count());

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'Round 3: please provide yet another photo of the structure.',
            ])
            ->assertSessionHasErrors('question');

        $this->assertSame(2, $complaint->informationRequests()->count(), 'The cap must hold.');
    }

    public function test_verifying_closes_any_outstanding_information_request(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $complaint), [
                'question' => 'Please send a photo that clearly shows the damaged signage.',
            ]);

        $this->assertNotNull($complaint->fresh()->outstandingInformationRequest());

        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'elevated',
                'verification_method' => VerificationMethod::Desk->value,
            ])
            ->assertRedirect();

        $this->assertNull(
            $complaint->fresh()->outstandingInformationRequest(),
            'Settling the complaint must close the outstanding ask.',
        );
    }

    public function test_admin_may_verify_as_the_fallback(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $complaint = $this->complaint(department: $this->otherDepartment);

        $this->actingAs($admin)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertRedirect();

        $this->assertSame('verified', $complaint->fresh()->review_status);
    }

    private function staff(bool $canVerify = false, ?Department $department = null): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => ($department ?? $this->department)->id,
            'can_verify' => $canVerify,
        ]);
    }

    private function complaint(?Department $department = null): Complaint
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $citizen->id,
            'department_id' => ($department ?? $this->department)->id,
            'category' => 'Road Damage',
            'title' => 'Fallen tree blocking the road',
            'description' => 'A large tree has fallen across the road near the barangay hall.',
            'address_text' => 'Near the barangay hall',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'is_public' => false,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
