<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\HoldReason;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Support\ComplaintProgress;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public ticket tracker.
 *
 * The ticket number is the only secret on this page, so these tests guard two
 * things at once: that the holder can always find their own record, and that
 * the page never shows more than progress.
 */
class GuestComplaintTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Private',
            'last_name' => 'Resident',
            'email' => 'private-resident@example.test',
            'display_alias' => 'Private alias',
        ]);

        // Keep each case on its own IP so the 20/minute route throttle does not
        // leak between tests that make several lookups.
        $this->withServerVariables([
            'REMOTE_ADDR' => '10.'.random_int(1, 254).'.'.random_int(1, 254).'.'.random_int(1, 254),
        ]);
    }

    public function test_a_guest_can_open_the_lookup_form(): void
    {
        $this->get(route('complaints.track'))
            ->assertOk()
            ->assertSee('action="'.route('complaints.track').'"', false)
            ->assertSee('name="ticket_id"', false)
            ->assertSee('method="GET"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_a_ticket_is_found_with_or_without_hyphens_and_in_any_case(): void
    {
        $complaint = $this->complaint([
            'status' => ComplaintStatus::InProgress->value,
            'ticket_id' => 'COMP-2026-A7K9P',
        ]);

        foreach ([
            'COMP-2026-A7K9P',
            'COMP2026A7K9P',
            'comp 2026 a7k9p',
            'COMP–2026–A7K9P', // en dashes, as a phone keyboard often produces
        ] as $variant) {
            $this->get(route('complaints.track', ['ticket_id' => $variant]))
                ->assertOk()
                ->assertSee($complaint->ticket_id)
                ->assertSee($this->department->name)
                ->assertSee(ComplaintStatus::InProgress->label());
        }
    }

    public function test_a_legacy_numeric_ticket_still_works(): void
    {
        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-00001',
            'status' => ComplaintStatus::UnderReview->value,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => 'comp202600001']))
            ->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee(ComplaintStatus::UnderReview->label());
    }

    public function test_an_unknown_ticket_gets_one_generic_message(): void
    {
        $complaint = $this->complaint([
            'title' => 'A private title that must not appear',
            'description' => 'A private description that must not appear.',
        ]);

        $this->get(route('complaints.track', ['ticket_id' => 'COMP-2026-ZZZZZ']))
            ->assertOk()
            ->assertSee(__('complaint_track.lookup.not_found_title'))
            ->assertDontSee($complaint->title)
            ->assertDontSee('A private description that must not appear.');
    }

    public function test_soft_deleted_and_confirmed_spam_complaints_are_not_found(): void
    {
        $deleted = $this->complaint([
            'ticket_id' => 'COMP-2026-DEL01',
            'title' => 'Deleted complaint title',
        ]);
        $deleted->delete();

        $spam = $this->complaint([
            'ticket_id' => 'COMP-2026-SPAM1',
            'title' => 'Confirmed spam title',
            'spam_status' => 'spam',
            'spam_confirmed_at' => now(),
        ]);

        foreach ([$deleted->ticket_id, $spam->ticket_id] as $ticket) {
            $this->get(route('complaints.track', ['ticket_id' => $ticket]))
                ->assertOk()
                ->assertSee(__('complaint_track.lookup.not_found_title'))
                ->assertDontSee('Deleted complaint title')
                ->assertDontSee('Confirmed spam title');
        }
    }

    public function test_the_result_shows_progress_but_never_the_private_record(): void
    {
        $complaint = $this->complaint([
            'title' => 'Secret road problem',
            'description' => 'Secret description at 123 Hidden Street.',
            'address_text' => '123 Hidden Street, Barangay VI, Daet',
            'barangay' => 'Barangay VI (Centro)',
            'category' => 'road_damage',
            'image_path' => 'complaints/secret-photo.png',
            'image_paths' => ['complaints/secret-photo.png'],
            'status' => ComplaintStatus::InProgress->value,
            // Not publicly visible: /track must stay progress-only.
            'is_public' => false,
            'review_status' => 'pending',
            'verified_at' => null,
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'The office sent a crew to the site.',
            'public_comment' => 'The office sent a crew to the site.',
            'is_internal' => false,
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::InProgress->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'Internal note that must stay private.',
            'is_internal' => true,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee($this->department->name)
            ->assertSee('The office sent a crew to the site.')
            ->assertSee(ComplaintStatus::InProgress->label())
            ->assertSee(__('transparency.show.progress'))
            ->assertDontSee('Internal note that must stay private.')
            ->assertDontSee('Secret road problem')
            ->assertDontSee('Secret description at 123 Hidden Street.')
            ->assertDontSee('123 Hidden Street')
            ->assertDontSee('Barangay VI (Centro)')
            ->assertDontSee('Private Resident')
            ->assertDontSee('Private alias')
            ->assertDontSee('private-resident@example.test')
            ->assertDontSee('road_damage')
            ->assertDontSee('secret-photo.png');
    }

    public function test_a_private_but_unverified_complaint_is_still_trackable_by_its_ticket_holder(): void
    {
        $complaint = $this->complaint([
            'is_public' => false,
            'review_status' => 'pending',
            'status' => ComplaintStatus::Submitted->value,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee($complaint->ticket_id)
            ->assertSee(ComplaintStatus::Submitted->label());
    }

    public function test_each_status_marks_the_right_step_current(): void
    {
        foreach ([
            [ComplaintStatus::Submitted, ComplaintStatus::Submitted],
            [ComplaintStatus::UnderReview, ComplaintStatus::UnderReview],
            [ComplaintStatus::InProgress, ComplaintStatus::InProgress],
            [ComplaintStatus::Resolved, ComplaintStatus::Resolved],
        ] as $index => [$status, $current]) {
            $complaint = $this->complaint([
                'ticket_id' => 'COMP-2026-ST'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'status' => $status->value,
            ]);

            $steps = ComplaintProgress::steps($complaint, collect());
            $step = collect($steps)->firstWhere('status', $current->value);

            $this->assertSame('current', $step['state'], "{$status->value} must mark {$current->value} current.");
        }

        $closed = $this->complaint([
            'ticket_id' => 'COMP-2026-CLOS1',
            'status' => ComplaintStatus::Closed->value,
        ]);
        $closedSteps = ComplaintProgress::steps($closed, collect());

        $this->assertSame(
            ['complete', 'complete', 'complete', 'complete', 'complete'],
            array_column($closedSteps, 'state'),
        );
    }

    public function test_rejection_stops_at_the_last_reached_status_not_the_oldest_log(): void
    {
        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-REJ01',
            'status' => ComplaintStatus::Rejected->value,
        ]);

        // Newest first is how the helper receives logs; the rejection row also
        // records where the complaint was before it was stopped.
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::Submitted->value,
            'is_internal' => false,
        ]);
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::Submitted->value,
            'new_status' => ComplaintStatus::UnderReview->value,
            'is_internal' => false,
        ]);
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'is_internal' => false,
        ]);
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->resident->id,
            'previous_status' => ComplaintStatus::InProgress->value,
            'new_status' => ComplaintStatus::Rejected->value,
            'is_internal' => false,
        ]);

        $steps = collect(ComplaintProgress::steps($complaint, $complaint->logs()->get()))
            ->keyBy('status');

        $this->assertSame('complete', $steps['Submitted']['state']);
        $this->assertSame('complete', $steps['Under Review']['state']);
        $this->assertSame('rejected', $steps['In Progress']['state']);
        $this->assertSame('stopped', $steps['Resolved']['state']);
        $this->assertSame('stopped', $steps['Closed']['state']);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('transparency.show.rejected_stage'))
            ->assertSee(__('complaint_track.result.rejected_title'));
    }

    public function test_a_hold_shows_the_public_note_and_expected_resume_date(): void
    {
        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-HOLD1',
            'status' => ComplaintStatus::InProgress->value,
            'hold_reason' => HoldReason::AwaitingMaterials->value,
            'hold_note' => 'Waiting for asphalt before the crew can return.',
            'hold_internal_note' => 'The internal budget note must stay private.',
            'hold_expected_resume_at' => now()->addWeek(),
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee('Waiting for asphalt before the crew can return.')
            ->assertSee($complaint->hold_expected_resume_at->translatedFormat('M j, Y'))
            ->assertDontSee('The internal budget note must stay private.');
    }

    public function test_an_open_information_request_shows_a_neutral_notice_without_the_question(): void
    {
        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-INFO1',
            'status' => ComplaintStatus::UnderReview->value,
        ]);

        ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $this->resident->id,
            'question' => 'Please send the exact pole number and a closer photo.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('complaint_track.result.waiting_title'))
            ->assertDontSee('Please send the exact pole number and a closer photo.');
    }

    public function test_an_empty_timeline_uses_the_public_empty_state(): void
    {
        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-EMPT1',
            'status' => ComplaintStatus::Submitted->value,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('transparency.show.no_updates'))
            ->assertSee(__('transparency.show.next_update'));
    }

    public function test_the_owner_is_sent_to_the_full_ticket(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->resident)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertRedirect(route('complaints.show', $complaint));
    }

    public function test_an_unverified_or_password_blocked_owner_stays_on_the_limited_page(): void
    {
        $complaint = $this->complaint();

        $this->resident->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->resident)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('complaint_track.result.title'));

        $this->resident->forceFill([
            'email_verified_at' => now(),
            'must_change_password' => true,
        ])->save();

        $this->actingAs($this->resident)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('complaint_track.result.title'));
    }

    public function test_another_citizen_is_not_redirected(): void
    {
        $complaint = $this->complaint();
        $other = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($other)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('complaint_track.result.title'));
    }

    public function test_authorized_staff_and_the_admin_are_sent_to_their_full_ticket(): void
    {
        $complaint = $this->complaint();

        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertRedirect(route('admin.complaints.show', $complaint));
    }

    public function test_staff_from_another_office_only_sees_the_limited_page(): void
    {
        $complaint = $this->complaint();
        $otherOffice = Department::factory()->create();
        $otherStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $otherOffice->id,
            'can_verify' => true,
        ]);

        $this->actingAs($otherStaff)
            ->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee(__('complaint_track.result.title'))
            ->assertDontSee('private-resident@example.test');
    }

    public function test_the_twenty_first_lookup_in_a_minute_is_throttled(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get(route('complaints.track'))->assertOk();
        }

        $this->get(route('complaints.track'))->assertStatus(429);
    }

    public function test_the_filipino_page_renders_translated_copy(): void
    {
        $this->withSession(['locale' => 'fil']);

        $complaint = $this->complaint([
            'ticket_id' => 'COMP-2026-FIL01',
            'status' => ComplaintStatus::InProgress->value,
        ]);

        $this->get(route('complaints.track', ['ticket_id' => $complaint->ticket_id]))
            ->assertOk()
            ->assertSee('Progreso ng reklamo')
            ->assertSee('Itinalagang tanggapan')
            ->assertSee($this->department->name)
            ->assertDontSee('complaint_track.result.title');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function complaint(array $overrides = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'ticket_id' => 'COMP-2026-A7K9P',
        ], $overrides));
    }
}
