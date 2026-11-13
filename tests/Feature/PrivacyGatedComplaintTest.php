<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\PublicationStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Services\ComplaintPrivacyService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The complaints that name somebody.
 *
 * Two categories name a party: how you were treated at the LGU (a member of
 * personnel) and the plastic policy (a business). For those, the name of the
 * party never leaves the office, the complaint is filed private, and it
 * reaches the public register only when the office has settled it, the
 * information has been verified, the sensitive parts have been redacted, and
 * the Mayor's office has approved it.
 */
class PrivacyGatedComplaintTest extends TestCase
{
    use RefreshDatabase;

    private User $resident;
    private User $hrStaff;
    private User $mayorStaff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');

        $this->seed(DepartmentSeeder::class);

        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->hrStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('MENRO')->id,
            'can_verify' => true,
        ]);
        $this->mayorStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('OM')->id,
            'can_verify' => true,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ── Filing ──────────────────────────────────────────────────────────

    public function test_a_report_that_names_somebody_is_filed_private_even_if_the_resident_asks_for_publication(): void
    {
        $complaint = $this->file('plastic_usage', [
            'visibility' => 'public',
            'named_party' => 'Sari Sari Store',
        ]);

        $this->assertFalse($complaint->is_public, 'A report that names somebody must be filed private.');
        $this->assertFalse($complaint->isPubliclyVisible());
    }

    public function test_the_personnel_report_is_withdrawn_with_its_office(): void
    {
        $offered = collect(\App\Services\DepartmentRouter::getCategoryOptions())
            ->flatten(1)
            ->pluck('value')
            ->all();

        $this->assertNotContains('staff_treatment', $offered);
    }

    public function test_the_name_of_the_party_is_stored_on_the_complaint(): void
    {
        $complaint = $this->file('plastic_usage', [
            'named_party' => 'Sari Sari Store',
        ]);

        $this->assertSame('Sari Sari Store', $complaint->fresh()->namedParty());
        $this->assertNull($complaint->fresh()->namedPartyPosition());
    }

    public function test_a_report_about_a_business_records_the_business_name(): void
    {
        $complaint = $this->file('plastic_usage', [
            'named_party' => 'Sari Sari Store',
        ]);

        $this->assertSame('Sari Sari Store', $complaint->fresh()->namedParty());
        $this->assertTrue($complaint->fresh()->isPrivacyGated());
    }

    public function test_an_ordinary_report_is_not_gated(): void
    {
        $complaint = $this->file('garbage');

        $this->assertFalse($complaint->fresh()->isPrivacyGated());
    }

    // ── The name never leaves the office ───────────────────────────────

    public function test_the_name_of_the_party_never_appears_on_the_public_register(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->mayorStaff)
            ->post(route('staff.publication-requests.approve', $complaint));

        $complaint->refresh();

        $this->assertTrue($complaint->isPubliclyVisible());

        $response = $this->get(route('transparency.complaints.show', $complaint));

        $response->assertOk();
        $response->assertDontSee('Juan dela Cruz');
        // The redacted copy is what the public reads.
        $response->assertSee('A resident reports how they were treated at the counter');
    }

    public function test_the_name_of_the_party_is_not_part_of_the_public_search_text(): void
    {
        $complaint = $this->file('plastic_usage', [
            'named_party' => 'Sari Sari Store',
            'visibility' => 'public',
        ]);

        $feed = app(\App\Services\TransparencyFeed::class);
        $report = $feed->publicReport($complaint, app(\App\Services\BarangayLocator::class));

        $this->assertStringNotContainsString('Juan dela Cruz', $report['search_text']);
        $this->assertStringNotContainsString('Juan dela Cruz', $report['title']);
        $this->assertStringNotContainsString('Juan dela Cruz', $report['raw_title']);
    }

    // ── The publication gate ───────────────────────────────────────────

    public function test_the_office_cannot_ask_for_publication_before_the_report_is_settled_verified_and_redacted(): void
    {
        $complaint = $this->gatedComplaint();

        $this->actingAs($this->hrStaff)
            ->post(route('staff.complaints.publication-request', $complaint))
            ->assertSessionHasErrors('publication');

        $this->assertNull($complaint->fresh()->publicationStatus());
    }

    public function test_the_office_asks_the_mayors_office_once_the_report_is_ready(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $this->actingAs($this->hrStaff)
            ->post(route('staff.complaints.publication-request', $complaint))
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(PublicationStatus::Pending, $complaint->fresh()->publicationStatus());
    }

    public function test_the_mayors_office_approves_and_the_redacted_report_is_published(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->mayorStaff)
            ->post(route('staff.publication-requests.approve', $complaint))
            ->assertRedirect(route('staff.publication-requests.index'));

        $complaint->refresh();

        $this->assertSame(PublicationStatus::Approved, $complaint->publicationStatus());
        $this->assertTrue($complaint->isPubliclyVisible());
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'publication_status' => PublicationStatus::Approved->value,
        ]);
    }

    public function test_the_mayors_office_declines_and_the_report_stays_private(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->mayorStaff)
            ->post(route('staff.publication-requests.decline', $complaint), [
                'publication_note' => 'Publishing this would identify the person named in it.',
            ])
            ->assertRedirect(route('staff.publication-requests.index'));

        $complaint->refresh();

        $this->assertSame(PublicationStatus::Declined, $complaint->publicationStatus());
        $this->assertFalse($complaint->isPubliclyVisible());
    }

    public function test_the_resident_is_told_when_the_report_is_published(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->mayorStaff)
            ->post(route('staff.publication-requests.approve', $complaint));

        $this->assertSame(
            1,
            Notification::where('user_id', $this->resident->id)
                ->where('type', Notification::COMPLAINT_PUBLICATION_APPROVED)
                ->count(),
        );
    }

    public function test_the_resident_is_told_when_the_report_is_not_published(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->mayorStaff)
            ->post(route('staff.publication-requests.decline', $complaint), [
                'publication_note' => 'This would identify the person named in it.',
            ]);

        $this->assertSame(
            1,
            Notification::where('user_id', $this->resident->id)
                ->where('type', Notification::COMPLAINT_PUBLICATION_DECLINED)
                ->count(),
        );
    }

    public function test_an_office_other_than_the_mayors_cannot_approve_a_publication(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['publication_status' => PublicationStatus::Pending->value]);

        $this->actingAs($this->hrStaff)
            ->post(route('staff.publication-requests.approve', $complaint))
            ->assertForbidden();

        $this->assertNotSame(
            PublicationStatus::Approved,
            $complaint->fresh()->publicationStatus(),
        );
    }

    public function test_the_resident_cannot_publish_a_report_that_names_somebody(): void
    {
        $complaint = $this->file('plastic_usage', [
            'named_party' => 'Sari Sari Store',
        ]);

        $this->actingAs($this->resident)
            ->put(route('complaints.visibility.update', $complaint), [
                'visibility' => 'public',
            ])
            ->assertRedirect(route('complaints.show', $complaint));

        $this->assertFalse($complaint->fresh()->is_public);
        $this->assertFalse($complaint->fresh()->isPubliclyVisible());
    }

    public function test_the_resident_keeps_their_own_report_privacy_choice_on_an_ordinary_report(): void
    {
        $complaint = $this->file('garbage');

        $this->actingAs($this->resident)
            ->put(route('complaints.visibility.update', $complaint), [
                'visibility' => 'public',
            ]);

        $this->assertTrue($complaint->fresh()->is_public);
    }

    public function test_the_publication_queue_is_open_to_the_mayors_office_only(): void
    {
        $this->actingAs($this->mayorStaff)
            ->get(route('staff.publication-requests.index'))
            ->assertOk();

        $this->actingAs($this->hrStaff)
            ->get(route('staff.publication-requests.index'))
            ->assertForbidden();
    }

    public function test_the_mayors_office_opens_a_request_from_another_office_on_the_review_page(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update([
            'publication_status' => PublicationStatus::Pending->value,
            'publication_requested_at' => now(),
            'publication_requested_by' => $this->hrStaff->id,
        ]);

        // The queue's "Open the report" leads to the review page, not the
        // ticket the decider's department does not own.
        $this->actingAs($this->mayorStaff)
            ->get(route('staff.publication-requests.index'))
            ->assertOk()
            ->assertSee(route('staff.publication-requests.show', $complaint));

        $this->actingAs($this->mayorStaff)
            ->get(route('staff.publication-requests.show', $complaint))
            ->assertOk()
            ->assertSee('A resident reports how they were treated at the counter')
            ->assertSee('Approve publication')
            // The name of the party stays in the handling office.
            ->assertDontSee('Sari Sari Store');
    }

    public function test_the_review_page_is_refused_outside_the_mayors_office(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $this->actingAs($this->hrStaff)
            ->get(route('staff.publication-requests.show', $complaint))
            ->assertForbidden();
    }

    public function test_the_review_page_does_not_exist_for_an_ordinary_report(): void
    {
        $complaint = $this->file('garbage');

        $this->actingAs($this->mayorStaff)
            ->get(route('staff.publication-requests.show', $complaint))
            ->assertNotFound();
    }

    public function test_a_publication_notification_opens_the_review_page_not_the_ticket(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $notification = Notification::create([
            'user_id' => $this->mayorStaff->id,
            'type' => Notification::COMPLAINT_PUBLICATION_REQUESTED,
            'title' => 'MENRO asked to publish '.$complaint->ticket_id,
            'subject_type' => Complaint::class,
            'subject_id' => $complaint->id,
        ]);

        $this->actingAs($this->mayorStaff)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('staff.publication-requests.show', $complaint));
    }

    public function test_the_office_ticks_what_must_not_appear_and_the_system_fills_the_redacted_copy(): void
    {
        $complaint = $this->gatedComplaint();
        $complaint->update([
            'title' => 'Sari Sari Store gives plastic bags at the counter',
            'description' => 'Sari Sari Store gives plastic bags for every purchase, without exception, every time.',
        ]);

        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        // Ticking fills the fields; nothing is written until the office saves.
        $this->post(route('staff.complaints.redact', $complaint), ['strip_party' => '1'])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('status', __('staff.moderation.quick_done'))
            ->assertSessionHas('_old_input.redacted_title', 'an establishment gives plastic bags at the counter');

        $this->assertNull($complaint->fresh()->redacted_title);

        // The office reviews the filled-in copy and saves it as the public one.
        $this->post(route('staff.complaints.moderate', $complaint), [
            'redacted_title' => 'an establishment gives plastic bags at the counter',
            'redacted_description' => 'an establishment gives plastic bags for every purchase, without exception, every time.',
            'moderation_reason' => 'Name of the party removed from the public copy.',
        ])->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();

        $this->assertStringNotContainsString('Sari Sari Store', $complaint->redacted_title);
        $this->assertStringNotContainsString('Sari Sari Store', $complaint->redacted_description);
        $this->assertTrue(ComplaintPrivacyService::isRedacted($complaint));
    }

    public function test_a_redaction_that_cannot_be_proven_gone_is_replaced_with_a_safe_generic_copy(): void
    {
        // The raw copy does not contain the party's name verbatim, so no
        // amount of string removal can prove it is gone. The ticked copy is
        // replaced with a safe generic one instead.
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['redacted_title' => null, 'redacted_description' => null]);

        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $this->post(route('staff.complaints.redact', $complaint), ['strip_party' => '1'])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('_old_input.redacted_title', 'An establishment in the municipality was reported to the office');

        $this->assertNull($complaint->fresh()->redacted_title);
    }

    public function test_a_tick_is_required_for_the_quick_redaction(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update(['redacted_title' => null]);

        // The browser flow starts on the ticket page, which is why the
        // redirect back lands there.
        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $this->post(route('staff.complaints.redact', $complaint), [])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('error', __('staff.moderation.quick_nothing'));

        $this->assertNull($complaint->fresh()->redacted_title);
    }

    public function test_the_quick_redact_leaves_the_saved_copy_untouched_until_the_office_saves(): void
    {
        $complaint = $this->gatedComplaint(ready: true);
        $complaint->update([
            'redacted_title' => 'The office words',
            'redacted_description' => 'The office words, kept.',
        ]);

        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $this->post(route('staff.complaints.redact', $complaint), ['strip_party' => '1'])
            ->assertRedirect(route('staff.complaints.show', $complaint))
            ->assertSessionHas('_old_input.redacted_title', 'An establishment in the municipality was reported to the office');

        // The office's saved copy is untouched — saving is what writes.
        $this->assertSame('The office words', $complaint->fresh()->redacted_title);
    }

    public function test_the_quick_redact_is_a_verifier_only_action(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $nonVerifier = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('MENRO')->id,
            'can_verify' => false,
        ]);

        $this->actingAs($nonVerifier)
            ->post(route('staff.complaints.redact', $complaint), ['strip_party' => '1'])
            ->assertForbidden();
    }

    public function test_a_gated_report_cannot_be_filed_without_the_resident_acknowledging_it(): void
    {
        $this->actingAs($this->resident)
            ->post(route('complaints.store'), [
                'category' => 'plastic_usage',
                'title' => 'How I was treated at the counter',
                'description' => 'A long enough description of what happened at the counter this morning.',
                'image' => $this->complaintEvidence(),
                'terms' => '1',
            ])
            ->assertSessionHasErrors('privacy_ack');
    }

    public function test_the_publication_checklist_shows_the_gate_states(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee(__('staff.publication.checklist_title'))
            ->assertSee(__('staff.publication.checklist_redacted'));

        $ordinary = $this->file('garbage');

        $this->actingAs($this->hrStaff)
            ->get(route('staff.complaints.show', $ordinary))
            ->assertOk()
            ->assertDontSee(__('staff.publication.checklist_title'));
    }

    public function test_the_resident_is_told_when_the_office_asks_for_publication(): void
    {
        $complaint = $this->gatedComplaint(ready: true);

        $this->actingAs($this->hrStaff)
            ->post(route('staff.complaints.publication-request', $complaint), [
                'publication_note' => 'Asking the Mayor\'s office to publish.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(
            1,
            Notification::where('user_id', $this->resident->id)
                ->where('type', Notification::COMPLAINT_PUBLICATION_REQUESTED)
                ->where('subject_id', $complaint->id)
                ->count(),
        );
    }

    public function test_the_mayors_office_is_the_approval_office(): void
    {
        $this->assertSame('OM', ComplaintPrivacyService::approvalOfficeCode());
        $this->assertTrue(ComplaintPrivacyService::canApprove($this->mayorStaff));
        $this->assertTrue(ComplaintPrivacyService::canApprove($this->admin));
        $this->assertFalse(ComplaintPrivacyService::canApprove($this->hrStaff));
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function department(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    private function file(string $category, array $fields = []): Complaint
    {
        $this->actingAs($this->resident)->post(route('complaints.store'), array_merge([
            'category' => $category,
            'title' => 'How I was treated at the counter',
            'description' => 'A long enough description of what happened at the counter this morning.',
            // The categories that ask for evidence need it; the ones that do
            // not simply ignore it.
            'image' => $this->complaintEvidence(),
            'terms' => '1',
            // The categories that name somebody require the resident to
            // acknowledge the publication gate before filing.
            'privacy_ack' => '1',
        ], $fields))->assertSessionHasNoErrors();

        return Complaint::latest('id')->firstOrFail();
    }

    private function gatedComplaint(bool $ready = false): Complaint
    {
        $complaint = Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $this->resident->id,
            'department_id' => $this->department('MENRO')->id,
            'category' => 'plastic_usage',
            'title' => 'How I was treated at the counter',
            'description' => 'A long enough description of what happened at the counter this morning.',
            'status' => ComplaintStatus::Submitted->value,
            'review_status' => 'pending',
            'is_public' => false,
            'named_party' => 'Sari Sari Store',
            'content_fingerprint' => Str::random(40),
        ]);

        if (! $ready) {
            return $complaint;
        }

        $complaint->update([
            'status' => ComplaintStatus::Resolved->value,
            'review_status' => 'verified',
            'verified_at' => now(),
            'redacted_title' => 'A resident reports how they were treated at the counter',
            'redacted_description' => 'A resident reports a complaint about the service they received at the counter.',
        ]);

        return $complaint->fresh();
    }
}
