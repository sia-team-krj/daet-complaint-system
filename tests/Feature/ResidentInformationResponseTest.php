<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\InformationRequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResidentInformationResponseTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $resident;

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();

        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->complaint = $this->makeComplaint();
    }

    public function test_the_resident_can_answer_a_verifiers_request(): void
    {
        $request = $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Here is a closer photo. The pole number 4412 is readable now.',
            ])
            ->assertRedirect();

        $request->refresh();

        $this->assertSame(InformationRequestStatus::Answered, $request->status);
        $this->assertStringContainsString('4412', $request->response_text);
        $this->assertNotNull($request->responded_at);
    }

    public function test_the_complaint_shows_the_outstanding_question_to_its_owner(): void
    {
        $this->openRequest(question: 'The streetlight photo is too dark to read the pole number.');

        $this->actingAs($this->resident)
            ->get(route('complaints.show', $this->complaint))
            ->assertOk()
            ->assertSee('The department needs more information')
            ->assertSee('The streetlight photo is too dark to read the pole number.')
            ->assertSee(route('complaints.information.store', $this->complaint), false);
    }

    public function test_the_reply_form_disappears_once_the_resident_has_answered(): void
    {
        $request = $this->openRequest();
        $request->update([
            'status' => InformationRequestStatus::Answered->value,
            'response_text' => 'Sent a clearer photo.',
            'responded_at' => now(),
        ]);

        $this->actingAs($this->resident)
            ->get(route('complaints.show', $this->complaint))
            ->assertOk()
            ->assertSee('We received your reply')
            ->assertDontSee('The streetlight photo is too dark');
    }

    public function test_a_resident_can_attach_more_photos(): void
    {
        $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Attaching two clearer shots of the damaged signage.',
                'images' => $this->complaintEvidenceSet(2),
            ])
            ->assertRedirect();

        $paths = $this->complaint->informationRequests()->latest('id')->first()->response_image_paths;

        $this->assertCount(2, $paths);
        Storage::disk('minio')->assertExists($paths[0]);
    }

    public function test_more_than_five_photos_are_rejected(): void
    {
        $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Attempting to attach far too many photographs at once.',
                'images' => $this->complaintEvidenceSet(6),
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(InformationRequestStatus::Open, $this->complaint->informationRequests()->latest('id')->first()->status);
    }

    public function test_another_resident_cannot_answer_someone_elses_request(): void
    {
        $this->openRequest();

        $intruder = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($intruder)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'This is not my complaint and I should not be able to answer it.',
            ])
            ->assertForbidden();

        $this->assertSame(InformationRequestStatus::Open, $this->complaint->informationRequests()->latest('id')->first()->status);
    }

    public function test_a_resident_cannot_reply_when_nothing_is_being_asked(): void
    {
        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'There is no open request, so this should be refused outright.',
            ])
            ->assertSessionHasErrors('complaint');
    }

    public function test_a_vague_reply_is_rejected(): void
    {
        $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'ok',
            ])
            ->assertSessionHasErrors('response');

        $this->assertSame(InformationRequestStatus::Open, $this->complaint->informationRequests()->latest('id')->first()->status);
    }

    public function test_a_resident_cannot_reply_twice_to_the_same_request(): void
    {
        $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'First reply with a clearer photograph of the pole number.',
            ]);

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Attempting a second reply to a request already answered.',
            ])
            ->assertSessionHasErrors('complaint');

        $this->assertSame(1, $this->complaint->informationRequests()->count());
    }

    public function test_guests_cannot_answer_anything(): void
    {
        $this->openRequest();

        $this->post(route('complaints.information.store', $this->complaint), [
            'response' => 'A logged-out visitor should never reach this endpoint.',
        ])->assertRedirect(route('login'));
    }

    public function test_admin_can_grant_and_revoke_verification_rights(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $staffMember = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.staff.toggle-verification', $staffMember))
            ->assertRedirect();

        $this->assertTrue($staffMember->fresh()->can_verify);
        $this->assertTrue($staffMember->fresh()->canVerifyComplaints());

        $this->actingAs($admin)
            ->post(route('admin.staff.toggle-verification', $staffMember))
            ->assertRedirect();

        $this->assertFalse($staffMember->fresh()->can_verify);
        $this->assertFalse($staffMember->fresh()->canVerifyComplaints());
    }

    public function test_verification_rights_cannot_be_changed_on_a_non_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($admin)
            ->post(route('admin.staff.toggle-verification', $citizen))
            ->assertSessionHas('error');

        $this->assertFalse($citizen->fresh()->can_verify);
    }

    public function test_non_admins_cannot_grant_verification_rights(): void
    {
        $verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);

        $this->actingAs($verifier)
            ->post(route('admin.staff.toggle-verification', $target))
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($target->fresh()->can_verify);
    }

    public function test_staff_page_shows_who_holds_verification_rights(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Make verifier')
            ->assertSee('Revoke verifier')
            ->assertSee('Verifier')
            ->assertSee(route('admin.staff.toggle-verification', $verifier), false);
    }

    public function test_the_verification_nav_link_is_gated_on_the_capability(): void
    {
        $regular = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => false,
        ]);
        $verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);

        $this->actingAs($regular)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertDontSee('Needs verification');

        $this->actingAs($verifier)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Needs verification')
            ->assertSee('Waiting on resident')
            ->assertSee(route('staff.complaints.index', ['review' => 'pending']), false);
    }

    public function test_the_verification_link_filters_the_queue_rather_than_opening_a_new_page(): void
    {
        $verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);

        // Same queue, pre-filtered. No parallel dashboard to maintain.
        $this->actingAs($verifier)
            ->get(route('staff.complaints.index', ['review' => 'needs_information']))
            ->assertOk()
            ->assertSee('Complaint queue')
            ->assertSee('Needs information');
    }

    public function test_the_public_register_states_how_the_report_was_checked(): void
    {
        $complaint = $this->makeComplaint();
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
            'is_public' => true,
            'verification_method' => VerificationMethod::SiteVisit->value,
        ]);

        // The register row shows a plain "Verified" pill; the public detail
        // page carries the honest claim, not a single vague "verified".
        $this->get(route('transparency'))
            ->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee('Verified')
            ->assertDontSee('Verification not recorded');

        $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Site attended');
    }

    public function test_a_desk_check_is_labelled_differently_from_a_site_visit(): void
    {
        $complaint = $this->makeComplaint();
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'routine',
            'is_public' => true,
            'verification_method' => VerificationMethod::Desk->value,
        ]);

        // The method itself lives on the detail page; the desk check and the
        // site visit can never read the same there.
        $detail = $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk();

        $detail->assertSee('Report reviewed')
            ->assertDontSee('Site attended');
    }

    public function test_the_public_detail_page_shows_the_verification_method(): void
    {
        $complaint = $this->makeComplaint();
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'urgent',
            'is_public' => true,
            'verification_method' => VerificationMethod::DuplicateMatch->value,
        ]);

        $this->get(route('transparency.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Already reported');
    }

    public function test_admin_review_uses_the_same_rules_as_the_department_verifier(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $complaint = $this->makeComplaint();
        $complaint->update(['is_public' => true, 'review_status' => 'verified', 'confirmed_priority' => 'urgent']);

        // Admin and the department verifier now share a single rule: neither
        // can touch public visibility. The field is ignored outright rather
        // than confirmed, because there is no longer a publication decision
        // here to confirm — the resident's choice is the only one that counts.
        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $complaint), [
                'review_status' => 'rejected',
                'is_public' => '0',
            ])
            ->assertSessionMissing('errors');

        $complaint->refresh();

        $this->assertSame('rejected', $complaint->review_status);
        $this->assertTrue(
            $complaint->is_public,
            'An admin must not be able to un-publish a complaint, any more than a verifier can.',
        );
    }

    public function test_admin_can_record_the_verification_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $complaint = $this->makeComplaint();

        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'elevated',
                'verification_method' => VerificationMethod::SiteVisit->value,
            ])
            ->assertRedirect();

        $this->assertSame(
            VerificationMethod::SiteVisit,
            $complaint->fresh()->verification_method,
        );
    }

    private function openRequest(string $question = 'Please send a closer photo showing the pole number.'): ComplaintInformationRequest
    {
        $verifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);

        $request = ComplaintInformationRequest::create([
            'complaint_id' => $this->complaint->id,
            'requested_by' => $verifier->id,
            'question' => $question,
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->complaint->update(['review_status' => ComplaintReviewStatus::NeedsInformation->value]);

        return $request;
    }

    private function makeComplaint(): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'Streetlight',
            'title' => 'Streetlight not working near the hall',
            'description' => 'The streetlight beside the barangay hall has been out for a week.',
            'address_text' => 'Beside the barangay hall',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'is_public' => false,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
