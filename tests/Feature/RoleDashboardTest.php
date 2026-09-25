<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_home_redirects_to_the_correct_role_workspace(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get('/')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($staff)
            ->get('/')
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_role_logo_links_to_the_matching_workspace(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard').'" class="navbar-logo', false);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('staff.dashboard').'" class="navbar-logo', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'" class="navbar-logo', false);
    }

    public function test_citizen_dashboard_only_shows_the_signed_in_residents_reports(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Alex',
        ]);
        $otherResident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Other',
        ]);

        $ownComplaint = Complaint::factory()->create([
            'user_id' => $resident->id,
            'department_id' => $department->id,
            'title' => 'My private report',
        ]);
        $otherComplaint = Complaint::factory()->create([
            'user_id' => $otherResident->id,
            'department_id' => $department->id,
            'title' => 'Another resident report',
        ]);

        $response = $this->actingAs($resident)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Your complaint center')
            ->assertSee('Private view:')
            ->assertSee('My reports')
            ->assertSee($ownComplaint->title)
            ->assertSee($ownComplaint->ticket_id)
            ->assertDontSee($otherComplaint->title)
            ->assertDontSee($otherComplaint->ticket_id)
            ->assertDontSee('Total Filed');
    }
}
