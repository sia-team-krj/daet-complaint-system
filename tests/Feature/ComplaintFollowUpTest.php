<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sub-ticketing — follow-ups against a finished complaint.
 *
 * When the office has resolved or closed a report and the resident is not
 * satisfied, they can file a follow-up that becomes a child of the original:
 * its own ticket number, status and SLA, seeded from the parent. The parent
 * stays closed, and only one follow-up may be open at a time.
 */
class ComplaintFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    private function finishedComplaint(User $owner, string $status = ComplaintStatus::Resolved->value): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $owner->id,
            'status' => $status,
        ]);
    }

    public function test_the_owner_can_file_a_follow_up_against_a_resolved_complaint(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user);

        $response = $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'The road is still broken',
            'description' => 'The repair never happened and the pothole came back.',
        ]);

        $followUp = Complaint::where('parent_id', $complaint->id)->sole();

        $response->assertRedirect(route('complaints.show', $followUp));
        $response->assertSessionHas('status');

        $this->assertSame($user->id, $followUp->user_id);
        $this->assertSame($complaint->category, $followUp->category);
        $this->assertSame(ComplaintStatus::Submitted->value, $followUp->status);
        $this->assertSame(ComplaintReviewStatus::Pending->value, $followUp->review_status);
        $this->assertSame($complaint->latitude, $followUp->latitude);
        $this->assertSame($complaint->longitude, $followUp->longitude);
        $this->assertNotSame($complaint->ticket_id, $followUp->ticket_id);
        $this->assertMatchesRegularExpression('/^COMP-\d{4}-[A-Z2-9]{5}$/', $followUp->ticket_id);

        // The parent is untouched — a follow-up is a new ticket, not a reopen.
        $this->assertSame(ComplaintStatus::Resolved->value, $complaint->fresh()->status);

        $this->assertNotNull(ComplaintLog::where('complaint_id', $followUp->id)->first());
    }

    public function test_the_owner_can_file_a_follow_up_against_a_closed_complaint(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user, ComplaintStatus::Closed->value);

        $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Still not settled for me',
            'description' => 'The issue was closed without anyone fixing it.',
        ])->assertRedirect();

        $this->assertNotNull(Complaint::where('parent_id', $complaint->id)->first());
    }

    public function test_only_one_follow_up_may_be_open_at_a_time(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user);

        $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'First follow-up',
            'description' => 'The first follow-up is still waiting on the office.',
        ])->assertRedirect();

        $response = $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Second follow-up',
            'description' => 'This one should be refused while the first is open.',
        ]);

        $response->assertRedirect(route('complaints.show', $complaint));
        $response->assertSessionHas('error');
        $this->assertSame(1, Complaint::where('parent_id', $complaint->id)->count());
    }

    public function test_a_new_follow_up_can_be_filed_once_the_previous_one_is_finished(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user);

        $first = Complaint::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $complaint->id,
            'status' => ComplaintStatus::Resolved->value,
        ]);

        $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Another round',
            'description' => 'The problem came back again after the last fix.',
        ])->assertRedirect();

        $this->assertSame(2, Complaint::where('parent_id', $complaint->id)->count());
        $this->assertTrue(Complaint::where('parent_id', $complaint->id)->whereKeyNot($first->id)->exists());
    }

    public function test_somebody_else_cannot_file_a_follow_up(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $stranger = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($owner);

        $this->actingAs($stranger)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Not my complaint',
            'description' => 'I should not be able to follow up on someone else\'s report.',
        ])->assertForbidden();

        $this->assertSame(0, Complaint::where('parent_id', $complaint->id)->count());
    }

    public function test_staff_and_admins_cannot_file_a_follow_up_yet(): void
    {
        $owner = User::factory()->create(['role' => 'citizen']);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create(['role' => 'staff', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $complaint = $this->finishedComplaint($owner);

        $payload = [
            'title' => 'Office-filed follow-up',
            'description' => 'Sub-ticketing v1 is citizen self-service only.',
        ];

        $this->actingAs($staff)->post(route('complaints.follow_up.store', $complaint), $payload)
            ->assertForbidden();
        $this->actingAs($admin)->post(route('complaints.follow_up.store', $complaint), $payload)
            ->assertForbidden();

        $this->assertSame(0, Complaint::where('parent_id', $complaint->id)->count());
    }

    public function test_an_unfinished_complaint_cannot_receive_a_follow_up(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user, ComplaintStatus::InProgress->value);

        $response = $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Too early',
            'description' => 'The report has not been resolved or closed yet.',
        ]);

        $response->assertRedirect(route('complaints.show', $complaint));
        $response->assertSessionHas('error');
        $this->assertSame(0, Complaint::where('parent_id', $complaint->id)->count());
    }

    public function test_a_too_short_follow_up_is_refused_by_validation(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $complaint = $this->finishedComplaint($user);

        $response = $this->actingAs($user)->post(route('complaints.follow_up.store', $complaint), [
            'title' => 'Too short',
            'description' => 'short',
        ]);

        $response->assertSessionHasErrors(['description']);
        $this->assertSame(0, Complaint::where('parent_id', $complaint->id)->count());
    }

    public function test_the_follow_up_panel_is_shown_on_a_finished_complaint_only(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $finished = $this->finishedComplaint($user);
        $open = $this->finishedComplaint($user, ComplaintStatus::InProgress->value);

        $response = $this->actingAs($user)->get(route('complaints.show', $finished));
        $response->assertOk();
        $response->assertSee(__('complaint_show.follow_up.submit'), false);

        $openPage = $this->actingAs($user)->get(route('complaints.show', $open));
        $openPage->assertOk();
        $openPage->assertDontSee(__('complaint_show.follow_up.submit'), false);
    }
}
