<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Support\TicketFingerprint;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The live ticket panel.
 *
 * A ticket is worked by more than the person looking at it. These tests pin
 * what the panel is told when the case moves: no change means no noise, a
 * change names the actor and the new status, and the page never lets a live
 * update replace the form somebody is typing into.
 */
class ComplaintLiveTicketTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

    private User $admin;

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
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_the_ticket_page_carries_the_live_panel_and_its_fingerprint(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $response = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee('data-live-ticket', false);
        $response->assertSee('data-live-ticket-notice', false);
        $response->assertSee('data-ticket-status-pill', false);
        $response->assertSee('data-ticket-status-label', false);
        $response->assertSee('status-pill under_review', false);
        $response->assertSee('status-pill__dot', false);
        $response->assertSee(route('staff.complaints.state', $complaint), false);
        $response->assertSee(TicketFingerprint::for($complaint->fresh('logs')), false);
        $response->assertSee(__('staff.live.dismiss'));
        $response->assertSee(__('staff.live.refresh'));
    }

    public function test_an_unchanged_ticket_reports_no_change(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);
        $fingerprint = TicketFingerprint::for($complaint);

        $this->actingAs($this->staff)
            ->getJson(route('staff.complaints.state', [
                'complaint' => $complaint,
                'fingerprint' => $fingerprint,
            ]))
            ->assertOk()
            ->assertJson([
                'changed' => false,
                'fingerprint' => $fingerprint,
            ]);
    }

    public function test_a_moved_ticket_names_the_actor_and_both_statuses(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);
        $fingerprint = TicketFingerprint::for($complaint);

        $other = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $other->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'The crew was dispatched to the site.',
        ]);
        $complaint->update(['status' => ComplaintStatus::InProgress->value]);

        $response = $this->actingAs($this->staff)
            ->getJson(route('staff.complaints.state', [
                'complaint' => $complaint,
                'fingerprint' => $fingerprint,
            ]))
            ->assertOk()
            ->assertJson([
                'changed' => true,
                'status' => ComplaintStatus::InProgress->value,
                'status_label' => ComplaintStatus::InProgress->label(),
                'status_key' => 'in_progress',
                'actor' => $other->full_name,
            ]);

        $message = $response->json('message');

        $this->assertStringContainsString($other->full_name, $message);
        $this->assertStringContainsString(ComplaintStatus::UnderReview->label(), $message);
        $this->assertStringContainsString(ComplaintStatus::InProgress->label(), $message);
    }

    public function test_an_internal_note_is_described_as_a_note_rather_than_a_move(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->staff->id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::UnderReview->value,
            'comment' => 'For the record: the resident called the office.',
            'is_internal' => true,
        ]);

        $this->actingAs($this->staff)
            ->getJson(route('staff.complaints.state', $complaint))
            ->assertOk()
            ->assertJson([
                'changed' => true,
                'message' => __('staff.live.note', ['actor' => $this->staff->full_name]),
            ]);
    }

    public function test_a_staff_member_from_another_department_is_refused(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);
        $outsider = User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'HLTH')->firstOrFail()->id,
        ]);

        $this->actingAs($outsider)
            ->getJson(route('staff.complaints.state', $complaint))
            ->assertForbidden();
    }

    public function test_a_person_opening_the_endpoint_is_sent_to_the_ticket(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.state', $complaint))
            ->assertRedirect(route('staff.complaints.show', $complaint));
    }

    public function test_a_guest_cannot_poll_the_ticket(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->getJson(route('staff.complaints.state', $complaint))
            ->assertUnauthorized();
    }

    // ── Admin ticket page ──────────────────────────────────────────

    public function test_the_admin_ticket_page_carries_the_live_panel_and_its_fingerprint(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee('data-live-ticket', false);
        $response->assertSee('data-live-ticket-notice', false);
        $response->assertSee('data-ticket-status-pill', false);
        $response->assertSee('data-ticket-status-label', false);
        $response->assertSee('status-pill', false);
        $response->assertSee('admin-status--under_review', false);
        $response->assertSee('status-pill__dot', false);
        $response->assertSee(route('admin.complaints.state', $complaint), false);
        $response->assertSee(TicketFingerprint::for($complaint->fresh('logs')), false);
        $response->assertSee(__('staff.live.dismiss'));
        $response->assertSee(__('staff.live.refresh'));
    }

    public function test_an_admin_unchanged_ticket_reports_no_change(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);
        $fingerprint = TicketFingerprint::for($complaint);

        $this->actingAs($this->admin)
            ->getJson(route('admin.complaints.state', [
                'complaint' => $complaint,
                'fingerprint' => $fingerprint,
            ]))
            ->assertOk()
            ->assertJson([
                'changed' => false,
                'fingerprint' => $fingerprint,
            ]);
    }

    public function test_an_admin_moved_ticket_names_the_actor_and_both_statuses(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);
        $fingerprint = TicketFingerprint::for($complaint);

        $other = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $other->id,
            'previous_status' => ComplaintStatus::UnderReview->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'The crew was dispatched to the site.',
        ]);
        $complaint->update(['status' => ComplaintStatus::InProgress->value]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.complaints.state', [
                'complaint' => $complaint,
                'fingerprint' => $fingerprint,
            ]))
            ->assertOk()
            ->assertJson([
                'changed' => true,
                'status' => ComplaintStatus::InProgress->value,
                'status_label' => ComplaintStatus::InProgress->label(),
                'status_key' => 'in_progress',
                'actor' => $other->full_name,
            ]);

        $message = $response->json('message');

        $this->assertStringContainsString($other->full_name, $message);
        $this->assertStringContainsString(ComplaintStatus::UnderReview->label(), $message);
        $this->assertStringContainsString(ComplaintStatus::InProgress->label(), $message);
    }

    public function test_an_admin_internal_note_is_described_as_a_note_rather_than_a_move(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->admin->id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::UnderReview->value,
            'comment' => 'For the record: the resident called the office.',
            'is_internal' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.complaints.state', $complaint))
            ->assertOk()
            ->assertJson([
                'changed' => true,
                'message' => __('staff.live.note', ['actor' => $this->admin->full_name]),
            ]);
    }

    public function test_an_admin_opening_the_endpoint_is_sent_to_the_ticket(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->actingAs($this->admin)
            ->get(route('admin.complaints.state', $complaint))
            ->assertRedirect(route('admin.complaints.show', $complaint));
    }

    public function test_a_guest_cannot_poll_the_admin_ticket(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview);

        $this->getJson(route('admin.complaints.state', $complaint))
            ->assertUnauthorized();
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
            'verified_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
        ]);
    }
}
