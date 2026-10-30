<?php

namespace Tests\Feature;

use App\Enums\InformationRequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $verifier;

    private User $resident;

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->verifier = $this->staff(canVerify: true);
        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->complaint = $this->makeComplaint();
    }

    public function test_a_resident_is_told_when_the_department_asks_for_more_evidence(): void
    {
        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.request-information', $this->complaint), [
                'question' => 'The streetlight photo is too dark to read the pole number.',
            ]);

        $notification = Notification::where('user_id', $this->resident->id)->sole();

        $this->assertSame(Notification::COMPLAINT_INFORMATION_REQUESTED, $notification->type);
        $this->assertStringContainsString($this->complaint->ticket_id, $notification->title);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            'The streetlight photo is too dark to read the pole number.',
            $notification->context['question'],
        );
    }

    public function test_a_delegated_verification_notifies_the_person_it_was_handed_to(): void
    {
        $second = $this->staff(canVerify: true);

        $this->actingAs($this->verifier)
            ->post(route('staff.complaints.verification.delegate', $this->complaint), [
                'verifying_staff_id' => $second->id,
            ]);

        $notification = Notification::where('user_id', $second->id)->sole();

        $this->assertSame(Notification::COMPLAINT_VERIFICATION_DELEGATED, $notification->type);

        // The person who handed it over must not be told about their own action.
        $this->assertSame(0, Notification::where('user_id', $this->verifier->id)->count());
    }

    public function test_the_verifier_who_asked_is_told_when_the_resident_replies(): void
    {
        $request = $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Here is a closer shot with the pole number visible.',
            ]);

        $notification = Notification::where('user_id', $this->verifier->id)->sole();

        $this->assertSame(Notification::COMPLAINT_INFORMATION_PROVIDED, $notification->type);
        $this->assertSame(1, $notification->context['round']);
    }

    public function test_a_resident_is_not_told_about_their_own_reply(): void
    {
        $this->openRequest();

        $this->actingAs($this->resident)
            ->post(route('complaints.information.store', $this->complaint), [
                'response' => 'Attaching the clearer photograph that was requested.',
            ]);

        $this->assertSame(0, Notification::where('user_id', $this->resident->id)->count());
    }

    public function test_the_department_is_told_when_a_report_waits_to_be_verified(): void
    {
        app(NotificationService::class)->complaintNeedsVerification($this->complaint);

        $notification = Notification::where('user_id', $this->verifier->id)->sole();

        $this->assertSame(Notification::COMPLAINT_NEEDS_VERIFICATION, $notification->type);
    }

    public function test_the_assigned_worker_is_never_notified_about_verification(): void
    {
        $worker = $this->staff();
        $this->complaint->update(['assigned_staff_id' => $worker->id]);

        app(NotificationService::class)->complaintNeedsVerification($this->complaint);

        // The worker cannot verify, so telling them to would be noise pointing at
        // an action they are not allowed to take.
        $this->assertSame(0, Notification::where('user_id', $worker->id)->count());
        $this->assertSame(1, Notification::where('user_id', $this->verifier->id)->count());
    }

    public function test_an_inactive_staff_member_is_not_notified(): void
    {
        $paused = $this->staff(canVerify: true);
        $paused->update(['is_active' => false]);

        app(NotificationService::class)->complaintNeedsVerification($this->complaint, actor: $this->verifier);

        $this->assertSame(0, Notification::where('user_id', $paused->id)->count());
    }

    public function test_another_departments_verifiers_are_never_notified(): void
    {
        $gso = Department::where('code', 'GSO')->firstOrFail();
        $outsider = User::factory()->create([
            'role' => 'staff',
            'department_id' => $gso->id,
            'can_verify' => true,
        ]);

        app(NotificationService::class)->complaintNeedsVerification($this->complaint);

        $this->assertSame(0, Notification::where('user_id', $outsider->id)->count());
    }

    public function test_the_bell_badge_caps_at_nine_plus(): void
    {
        $this->assertSame('', Notification::unreadBadgeFor($this->verifier->id));

        foreach (range(1, 4) as $index) {
            Notification::create([
                'user_id' => $this->verifier->id,
                'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
                'title' => "Item {$index}",
            ]);
        }

        $this->assertSame('4', Notification::unreadBadgeFor($this->verifier->id));

        foreach (range(5, 14) as $index) {
            Notification::create([
                'user_id' => $this->verifier->id,
                'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
                'title' => "Item {$index}",
            ]);
        }

        $this->assertSame('9+', Notification::unreadBadgeFor($this->verifier->id));
    }

    public function test_reading_a_notification_clears_it_from_the_badge(): void
    {
        $notification = Notification::create([
            'user_id' => $this->verifier->id,
            'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
            'title' => 'Something to look at',
        ]);

        $this->assertFalse($notification->isRead());
        $this->assertSame('1', Notification::unreadBadgeFor($this->verifier->id));

        $notification->markRead();

        $this->assertTrue($notification->fresh()->isRead());
        $this->assertSame('', Notification::unreadBadgeFor($this->verifier->id));
    }

    public function test_one_recipients_notifications_never_leak_to_another(): void
    {
        $other = $this->staff();

        Notification::create([
            'user_id' => $this->verifier->id,
            'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
            'title' => 'Private to the verifier',
        ]);

        $this->assertSame('1', Notification::unreadBadgeFor($this->verifier->id));
        $this->assertSame('', Notification::unreadBadgeFor($other->id));
        $this->assertSame(0, Notification::where('user_id', $other->id)->count());
    }

    public function test_stale_requests_surface_instead_of_rotting(): void
    {
        $request = $this->openRequest();
        $request->forceFill(['created_at' => now()->subDays(6)])->save();

        $stale = app(NotificationService::class)->staleRequests();

        $this->assertCount(1, $stale);
        $this->assertTrue($stale->first()->is($request));
    }

    public function test_a_fresh_request_is_not_treated_as_stale(): void
    {
        $this->openRequest();

        $this->assertCount(0, app(NotificationService::class)->staleRequests());
    }

    public function test_a_closed_request_never_counts_as_stale(): void
    {
        $request = $this->openRequest();
        $request->forceFill([
            'created_at' => now()->subDays(30),
            'status' => InformationRequestStatus::Resolved->value,
            'resolved_at' => now(),
        ])->save();

        $this->assertCount(0, app(NotificationService::class)->staleRequests());
    }

    public function test_the_queue_shows_how_long_a_resident_has_been_waiting(): void
    {
        $request = $this->openRequest();
        $request->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->actingAs($this->verifier)
            ->get(route('staff.complaints.index', ['review' => 'needs_information']))
            ->assertOk()
            ->assertSee('Waiting 5 days')
            ->assertSee('overdue');
    }

    public function test_the_queue_marks_a_fresh_wait_as_not_overdue(): void
    {
        $this->openRequest();

        $this->actingAs($this->verifier)
            ->get(route('staff.complaints.index', ['review' => 'needs_information']))
            ->assertOk()
            ->assertDontSee('overdue');
    }

    public function test_filing_a_complaint_tells_the_departments_verifiers(): void
    {
        $this->actingAs($this->resident)->post(route('complaints.store'), [
            'category' => 'road_damage',
            'title' => 'Fallen tree blocking the road near the hall',
            'description' => 'A large tree has fallen across the road and residents cannot pass.',
            'image' => $this->complaintEvidence(),
            'terms' => '1',
        ])->assertRedirect();

        $notification = Notification::where('user_id', $this->verifier->id)
            ->where('type', Notification::COMPLAINT_NEEDS_VERIFICATION)
            ->sole();

        $this->assertStringContainsString('COMP-', $notification->title);
    }

    public function test_a_status_change_tells_the_resident(): void
    {
        $complaint = $this->complaint;
        $complaint->update([
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);

        // Submitted -> Under Review needs no site documentation, so this exercises
        // the notification rather than the separate photo-evidence rule.
        $this->actingAs($this->verifier)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => 'Under Review',
                'note' => 'A crew has been assigned and the site was inspected today.',
            ])
            ->assertRedirect();

        $notification = Notification::where('user_id', $this->resident->id)->sole();

        $this->assertSame(Notification::COMPLAINT_STATUS_CHANGED, $notification->type);
        $this->assertStringContainsString('Under Review', $notification->title);
    }

    public function test_the_bell_shows_for_every_role_in_the_top_bar(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $byRole = [
            'staff' => [$this->verifier, route('staff.dashboard')],
            'citizen' => [$this->resident, route('dashboard')],
            'admin' => [$admin, route('admin.dashboard')],
        ];

        foreach ($byRole as $role => [$user, $homeRoute]) {
            Notification::create([
                'user_id' => $user->id,
                'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
                'title' => 'Something needs your attention',
            ]);
        }

        foreach ($byRole as $role => [$user, $homeRoute]) {
            $this->actingAs($user)
                ->get($homeRoute)
                ->assertOk()
                ->assertSee('id="notification-bell"', false)
                ->assertSee('aria-controls="notification-dropdown"', false)
                ->assertSee('nav-bell__badge', false)
                // Placed beside the account control, in the top bar.
                ->assertSeeInOrder(['id="notification-bell"', 'id="user-menu-btn"'], false);
        }
    }

    public function test_the_feed_row_uses_an_icon_anchor_and_a_bolded_subject(): void
    {
        Notification::create([
            'user_id' => $this->verifier->id,
            'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
            'title' => "{$this->complaint->ticket_id} is waiting to be verified",
            'subject_type' => Complaint::class,
            'subject_id' => $this->complaint->id,
            'context' => ['ticket_id' => $this->complaint->ticket_id],
        ]);

        $this->actingAs($this->verifier)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('notification-feed__icon', false)
            ->assertSee('notification-feed__headline', false)
            // The ticket id is the scannable entity, so it carries the weight.
            ->assertSeeInOrder(
                ['notification-feed__headline', '<strong>'.$this->complaint->ticket_id.'</strong>'],
                false,
            );
    }

    public function test_the_bell_is_not_hidden_by_the_compact_menu_breakpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);

        $byRole = [
            'staff' => [$this->verifier, route('staff.dashboard')],
            'citizen' => [$this->resident, route('dashboard')],
            'admin' => [$admin, route('admin.dashboard')],
        ];

        foreach ($byRole as $role => [$user, $homeRoute]) {
            $content = $this->actingAs($user)
                ->get($homeRoute)
                ->assertOk()
                ->getContent();

            $bellPosition = strpos($content, 'id="notification-bell"');
            $actionsPosition = strpos($content, '<ul class="nav-actions"');

            $this->assertNotFalse($bellPosition, "The {$role} bar has no bell.");
            $this->assertNotFalse($actionsPosition, "The {$role} bar has no action list.");
            $this->assertLessThan(
                $actionsPosition,
                $bellPosition,
                "The {$role} bell must sit outside .nav-actions, which is display:none below 1241px.",
            );
        }
    }

    public function test_opening_a_notification_marks_it_read_and_sends_the_reader_onward(): void
    {
        $notification = Notification::create([
            'user_id' => $this->verifier->id,
            'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
            'title' => 'A report is waiting',
            'subject_type' => Complaint::class,
            'subject_id' => $this->complaint->id,
        ]);

        $this->actingAs($this->verifier)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('staff.complaints.show', $this->complaint));

        $this->assertTrue($notification->fresh()->isRead());
    }

    public function test_a_residents_notification_sends_them_to_their_own_complaint(): void
    {
        $notification = Notification::create([
            'user_id' => $this->resident->id,
            'type' => Notification::COMPLAINT_STATUS_CHANGED,
            'title' => 'Your complaint moved',
            'subject_type' => Complaint::class,
            'subject_id' => $this->complaint->id,
        ]);

        $this->actingAs($this->resident)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('complaints.show', $this->complaint));
    }

    public function test_one_person_cannot_read_another_persons_notification(): void
    {
        $notification = Notification::create([
            'user_id' => $this->verifier->id,
            'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
            'title' => 'Private',
        ]);

        $other = $this->staff();

        $this->actingAs($other)
            ->get(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertFalse($notification->fresh()->isRead());
    }

    public function test_mark_all_read_clears_the_badge(): void
    {
        foreach (range(1, 3) as $index) {
            Notification::create([
                'user_id' => $this->verifier->id,
                'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
                'title' => "Item {$index}",
            ]);
        }

        $this->actingAs($this->verifier)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame('', Notification::unreadBadgeFor($this->verifier->id));
    }

    public function test_the_full_page_lists_notifications_and_paginates_at_twenty(): void
    {
        foreach (range(1, 24) as $index) {
            Notification::create([
                'user_id' => $this->verifier->id,
                'type' => Notification::COMPLAINT_NEEDS_VERIFICATION,
                'title' => "Item {$index}",
            ]);
        }

        $this->actingAs($this->verifier)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Item 24')
            ->assertDontSee('Item 4')
            ->assertSee('Item 21')
            ->assertSee('page=2', false);
    }

    public function test_the_stale_sweep_raises_a_notification_once_and_not_every_day(): void
    {
        $request = $this->openRequest();
        $request->forceFill(['created_at' => now()->subDays(6)])->save();

        $this->artisan('complaints:flag-stale-requests')
            ->assertSuccessful();

        $this->assertSame(1, Notification::where('type', Notification::COMPLAINT_INFORMATION_STALE)->count());

        // A second sweep must not re-notify, or people learn to ignore the badge.
        $this->artisan('complaints:flag-stale-requests')
            ->assertSuccessful();

        $this->assertSame(1, Notification::where('type', Notification::COMPLAINT_INFORMATION_STALE)->count());
    }

    private function openRequest(): ComplaintInformationRequest
    {
        $request = ComplaintInformationRequest::create([
            'complaint_id' => $this->complaint->id,
            'requested_by' => $this->verifier->id,
            'question' => 'Please send a closer photo showing the pole number.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $this->complaint->update(['review_status' => 'needs_information']);

        return $request;
    }

    private function staff(bool $canVerify = false): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => $canVerify,
        ]);
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
