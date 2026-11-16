<?php

namespace Tests\Feature;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An ordinary staff member works the cases the office hands them.
 *
 * A verifier — the Department Administrator, or staff with verification
 * rights — carries the office's whole queue. An ordinary staff member sees
 * only the complaints assigned to them: the ones assigned to them now, and
 * the ones they worked before they were passed on to somebody else.
 */
class StaffAssignedWorkScopeTest extends TestCase
{
    use RefreshDatabase;

    private Department $office;

    private User $worker;

    private User $verifier;

    private function office(): Department
    {
        if (! isset($this->office)) {
            $this->seed(DepartmentSeeder::class);
            $this->office = Department::where('code', 'ENG')->firstOrFail();
        }

        return $this->office;
    }

    private function worker(): User
    {
        if (! isset($this->worker)) {
            $this->worker = User::factory()->create([
                'role' => 'staff',
                'department_id' => $this->office()->id,
                'can_verify' => false,
            ]);
        }

        return $this->worker;
    }

    private function verifier(): User
    {
        if (! isset($this->verifier)) {
            $this->verifier = User::factory()->create([
                'role' => 'staff',
                'department_id' => $this->office()->id,
                'can_verify' => true,
            ]);
        }

        return $this->verifier;
    }

    private function complaint(array $attributes = []): Complaint
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        return Complaint::factory()->create(array_merge([
            'department_id' => $this->office()->id,
            'user_id' => $resident->id,
            'status' => ComplaintStatus::InProgress,
            'review_status' => ComplaintReviewStatus::Verified,
            'verified_at' => now(),
        ], $attributes));
    }

    private function recordAssignment(Complaint $complaint, ?User $assignee, User $actor): void
    {
        ActivityLog::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->full_name,
            'actor_role' => $actor->role,
            'department_id' => $complaint->department_id,
            'action' => 'complaint.assignment_updated',
            'subject_type' => (new Complaint)->getMorphClass(),
            'subject_id' => $complaint->id,
            'description' => 'Complaint assigned.',
            'metadata' => [
                'previous_assigned_staff_id' => null,
                'assigned_staff_id' => $assignee?->id,
            ],
            'created_at' => now(),
        ]);
    }

    public function test_a_non_verifier_sees_only_the_work_assigned_to_them(): void
    {
        $worker = $this->worker();
        $mine = $this->complaint(['assigned_staff_id' => $worker->id]);
        $someoneElses = $this->complaint(['assigned_staff_id' => $this->verifier()->id]);
        $unassigned = $this->complaint(['assigned_staff_id' => null]);

        $response = $this->actingAs($worker)->get(route('staff.complaints.index'))->assertOk();

        $response->assertSee($mine->ticket_id);
        $response->assertDontSee($someoneElses->ticket_id);
        $response->assertDontSee($unassigned->ticket_id);
    }

    public function test_a_non_verifier_cannot_open_a_complaint_that_is_not_theirs(): void
    {
        $worker = $this->worker();
        $notMine = $this->complaint(['assigned_staff_id' => $this->verifier()->id]);

        $this->actingAs($worker)
            ->get(route('staff.complaints.show', $notMine))
            ->assertForbidden();

        $mine = $this->complaint(['assigned_staff_id' => $worker->id]);

        $this->actingAs($worker)
            ->get(route('staff.complaints.show', $mine))
            ->assertOk();
    }

    public function test_work_that_was_reassigned_away_stays_readable(): void
    {
        $worker = $this->worker();
        $complaint = $this->complaint(['assigned_staff_id' => $this->verifier()->id]);
        $this->recordAssignment($complaint, $worker, $this->verifier());

        $this->actingAs($worker)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee($complaint->ticket_id);

        $this->actingAs($worker)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();
    }

    public function test_a_verifier_still_sees_the_whole_office(): void
    {
        $verifier = $this->verifier();
        $mine = $this->complaint(['assigned_staff_id' => $verifier->id]);
        $theirs = $this->complaint(['assigned_staff_id' => $this->worker()->id]);
        $unassigned = $this->complaint(['assigned_staff_id' => null]);

        $this->actingAs($verifier)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee($mine->ticket_id)
            ->assertSee($theirs->ticket_id)
            ->assertSee($unassigned->ticket_id);
    }

    public function test_the_dashboard_counts_only_the_work_the_person_can_see(): void
    {
        $worker = $this->worker();
        $mine = $this->complaint([
            'assigned_staff_id' => $worker->id,
            'status' => ComplaintStatus::InProgress,
        ]);
        $theirs = $this->complaint([
            'assigned_staff_id' => $this->verifier()->id,
            'status' => ComplaintStatus::InProgress,
        ]);

        $response = $this->actingAs($worker)->get(route('staff.dashboard'))->assertOk();

        $response->assertSee($mine->ticket_id);
        $response->assertDontSee($theirs->ticket_id);

        $this->actingAs($this->verifier())
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee($mine->ticket_id)
            ->assertSee($theirs->ticket_id);
    }

    public function test_the_nav_label_tells_the_person_what_the_list_is(): void
    {
        $worker = $this->worker();

        $this->actingAs($worker)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee(__('nav.my_work'));

        $this->actingAs($this->verifier())
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee(__('nav.complaint_queue'));
    }

    public function test_a_non_verifier_cannot_post_a_status_update_for_work_that_is_not_theirs(): void
    {
        $worker = $this->worker();
        $notMine = $this->complaint(['assigned_staff_id' => $this->verifier()->id]);

        $this->actingAs($worker)
            ->put(route('staff.complaints.update', $notMine), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'Trying to move a case that is not mine.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(ComplaintStatus::InProgress->value, $notMine->fresh()->status);
    }
}
