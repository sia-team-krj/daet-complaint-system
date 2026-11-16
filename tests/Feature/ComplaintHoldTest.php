<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintSlaState;
use App\Enums\HoldReason;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Services\ComplaintSlaService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A complaint the office cannot finish today.
 *
 * Some complaints are perfectly legitimate, verified, and still not
 * finishable this week — the asphalt is not in stock, the budget has not
 * been released, the work belongs to another body. The office records why,
 * says it out loud to the resident, and the deadline it owes pauses for as
 * long as it waits.
 */
class ComplaintHoldTest extends TestCase
{
    use RefreshDatabase;

    private User $resident;
    private User $staff;
    private User $otherStaff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');

        $this->seed(DepartmentSeeder::class);

        $this->resident = User::factory()->create(['role' => 'citizen']);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('ENG')->id,
            'can_verify' => true,
        ]);
        $this->otherStaff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department('MENRO')->id,
            'can_verify' => true,
        ]);
    }

    // ── Putting the work on hold ──────────────────────────────────────

    public function test_the_office_can_put_a_complaint_on_hold(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingMaterials->value,
                'hold_note' => 'The asphalt for this stretch of road is not in stock. The schedule is for next quarter.',
                'hold_expected_resume_at' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();

        $this->assertTrue($complaint->isOnHold());
        $this->assertSame(HoldReason::AwaitingMaterials, $complaint->holdReasonEnum());
        $this->assertNotNull($complaint->holdNote());
        $this->assertNotNull($complaint->hold_expected_resume_at);
    }

    public function test_the_hold_does_not_change_the_status(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ]);

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);
    }

    public function test_the_reason_is_required(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => '',
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHasErrors('hold_reason');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_the_resident_must_be_told_why(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => '',
            ])
            ->assertSessionHasErrors('hold_note');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_the_resident_is_told_the_work_is_on_hold(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ]);

        $this->assertSame(
            1,
            Notification::where('user_id', $this->resident->id)
                ->where('type', Notification::COMPLAINT_PUT_ON_HOLD)
                ->count(),
        );

        // And the note is in the public timeline, so the public register
        // shows the office being straight with the resident.
        $log = ComplaintLog::where('complaint_id', $complaint->id)
            ->where('is_internal', false)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('budget', $log->comment);
    }

    public function test_the_internal_note_is_never_shown_to_the_resident(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
                'hold_internal_note' => 'Budget request is in the 2027 line item.',
            ]);

        $internal = ComplaintLog::where('complaint_id', $complaint->id)
            ->where('is_internal', true)
            ->latest('id')
            ->first();

        $this->assertNotNull($internal);
        $this->assertStringContainsString('2027', $internal->comment);
    }

    public function test_an_office_cannot_hold_a_complaint_outside_its_own(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->otherStaff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_a_complaint_cannot_be_put_on_hold_twice(): void
    {
        $complaint = $this->complaint();
        $this->hold($complaint);

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHasErrors('hold');

        $this->assertSame(HoldReason::AwaitingMaterials, $complaint->fresh()->holdReasonEnum());
    }

    // ── A finished report has nothing left to pause ───────────────────

    public function test_the_pause_control_is_not_offered_on_a_resolved_report(): void
    {
        $complaint = $this->finishedComplaint(ComplaintStatus::Resolved);

        $staffPage = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint));

        $staffPage->assertOk();
        $staffPage->assertDontSee('Put the work on hold');
        $staffPage->assertDontSee(route('staff.complaints.hold', $complaint), false);

        $admin = User::factory()->create(['role' => 'admin']);

        $adminPage = $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint));

        $adminPage->assertOk();
        $adminPage->assertDontSee('Put the work on hold');
        $adminPage->assertDontSee(route('staff.complaints.hold', $complaint), false);
    }

    public function test_a_resolved_report_cannot_be_put_on_hold(): void
    {
        $complaint = $this->finishedComplaint(ComplaintStatus::Resolved);

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHasErrors('hold');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_a_rejected_report_cannot_be_put_on_hold(): void
    {
        $complaint = $this->finishedComplaint(ComplaintStatus::Rejected);

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHasErrors('hold');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_a_closed_report_cannot_be_put_on_hold(): void
    {
        $complaint = $this->finishedComplaint(ComplaintStatus::Closed);

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingFunds->value,
                'hold_note' => 'The budget for this repair has not been released yet.',
            ])
            ->assertSessionHasErrors('hold');

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    public function test_an_on_hold_report_can_still_be_taken_off_hold_once_it_is_resolved(): void
    {
        $complaint = $this->finishedComplaint(ComplaintStatus::Resolved);

        // A hold taken before the work was finished is not stranded by the
        // finish: the office still sees the state and can take it off.
        $complaint->update([
            'hold_reason' => HoldReason::AwaitingMaterials->value,
            'hold_note' => 'The asphalt for this stretch of road is not in stock.',
            'hold_started_at' => now()->subDay(),
            'hold_started_by' => $this->staff->id,
        ]);

        $page = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint));

        $page->assertOk();
        $page->assertDontSee('Put the work on hold');
        $page->assertSee('Take the hold off and carry on');

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.resume', $complaint), [
                'hold_note' => 'The materials arrived; the crew finished the repair.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertFalse($complaint->fresh()->isOnHold());
    }

    // ── The clock ──────────────────────────────────────────────────────

    public function test_a_complaint_on_hold_is_not_late(): void
    {
        $complaint = $this->complaint(verifiedDaysAgo: 40);

        $sla = app(ComplaintSlaService::class);

        $this->assertContains($complaint->id, $sla->overdueIds(), 'A complaint past its deadline is overdue before it is held.');

        $this->hold($complaint);

        $this->assertNotContains(
            $complaint->id,
            $sla->overdueIds(),
            'A complaint whose work is paused is waiting, not late.',
        );

        $this->assertSame(ComplaintSlaState::OnHold, $sla->summary($complaint->fresh())['state']);
    }

    public function test_the_time_spent_on_hold_is_added_back_to_the_deadline(): void
    {
        $complaint = $this->complaint(verifiedDaysAgo: 40);

        $sla = app(ComplaintSlaService::class);

        $before = $sla->summary($complaint)['deadline'];

        $this->hold($complaint, heldMinutes: 5);

        // The hold is lifted: the wait is credited back to the office.
        $complaint->update([
            'hold_reason' => null,
            'hold_note' => null,
            'hold_started_at' => null,
            'hold_total_minutes' => 4320,
        ]);

        $after = $sla->summary($complaint->fresh())['deadline'];

        $this->assertTrue(
            $after->greaterThan($before),
            'The deadline the office owes must move out by the time it waited.',
        );
    }

    // ── Carrying on again ──────────────────────────────────────────────

    public function test_the_office_can_take_the_hold_off_and_carry_on(): void
    {
        $complaint = $this->complaint();
        $this->hold($complaint);

        $this->actingAs($this->staff)
            ->post(route('staff.complaints.resume', $complaint), [
                'hold_note' => 'The materials arrived; the crew is back on site from Monday.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $complaint->refresh();

        $this->assertFalse($complaint->isOnHold());
        $this->assertNull($complaint->holdNote());
        $this->assertGreaterThan(0, $complaint->hold_total_minutes);

        $this->assertSame(
            1,
            Notification::where('user_id', $this->resident->id)
                ->where('type', Notification::COMPLAINT_HOLD_REMOVED)
                ->count(),
        );
    }

    public function test_the_resident_sees_the_hold_and_the_reason_on_their_ticket(): void
    {
        $complaint = $this->complaint();
        $this->hold($complaint);

        $response = $this->actingAs($this->resident)->get(route('complaints.show', $complaint));

        $response->assertOk();
        $response->assertSee('Waiting on materials');
        $response->assertSee('The asphalt for this stretch of road is not in stock');
    }

    public function test_the_administrator_can_see_what_is_blocked(): void
    {
        $complaint = $this->complaint();
        $this->hold($complaint);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.complaints.index', ['on_hold' => 1]));

        $response->assertOk();
        $response->assertSee($complaint->ticket_id);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function department(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    private function hold(Complaint $complaint, int $heldMinutes = 60): void
    {
        $this->actingAs($this->staff)
            ->post(route('staff.complaints.hold', $complaint), [
                'hold_reason' => HoldReason::AwaitingMaterials->value,
                'hold_note' => 'The asphalt for this stretch of road is not in stock. The schedule is for next quarter.',
            ])
            ->assertSessionHasNoErrors();

        if ($heldMinutes > 0) {
            $complaint->update(['hold_started_at' => now()->subMinutes($heldMinutes)]);
        }
    }

    private function finishedComplaint(ComplaintStatus $status): Complaint
    {
        $complaint = $this->complaint();
        $complaint->update(['status' => $status->value]);

        return $complaint->fresh();
    }

    private function complaint(int $verifiedDaysAgo = 1): Complaint
    {
        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) Complaint::count() + 1, 5, '0', STR_PAD_LEFT),
            'user_id' => $this->resident->id,
            'department_id' => $this->department('ENG')->id,
            'category' => 'road_damage',
            'title' => 'Large pothole on the main road',
            'description' => 'A large pothole has opened up on the main road near the market.',
            'status' => ComplaintStatus::InProgress->value,
            'review_status' => 'verified',
            'verified_at' => now()->subDays($verifiedDaysAgo),
            'is_public' => true,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
