<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_renders_the_shared_department_workspace(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Department overview')
            ->assertSee('Priority complaints')
            ->assertSee('Recent department activity')
            ->assertSee($department->name)
            ->assertSee('Complaint queue')
            ->assertSee('My activity')
            ->assertSee('Staff navigation');

        $this->assertSame(1, substr_count($response->getContent(), 'My activity'));
        $this->assertStringNotContainsString(route('dashboard'), $response->getContent());
    }

    public function test_staff_queue_route_only_returns_the_assigned_department(): void
    {
        $this->seed(DepartmentSeeder::class);
        $engineering = Department::where('code', 'ENG')->firstOrFail();
        $gso = Department::where('code', 'GSO')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $engineering->id,
        ]);
        $resident = User::factory()->create(['role' => 'citizen']);

        $ownComplaint = Complaint::factory()->create([
            'department_id' => $engineering->id,
            'user_id' => $resident->id,
            'title' => 'Engineering department complaint',
        ]);
        $otherComplaint = Complaint::factory()->create([
            'department_id' => $gso->id,
            'user_id' => $resident->id,
            'title' => 'General services complaint',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee($ownComplaint->ticket_id)
            ->assertSee($ownComplaint->title)
            ->assertDontSee($otherComplaint->ticket_id)
            ->assertDontSee($otherComplaint->title);
    }

    public function test_staff_activity_has_a_dedicated_route(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.activity.index'))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee('My handled complaints')
            ->assertSee('Staff navigation');
    }

    public function test_staff_complaint_detail_uses_the_staff_workspace_shell(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $complaint = Complaint::factory()->create([
            'department_id' => $department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Staff navigation')
            ->assertSee($complaint->ticket_id);
    }
}
