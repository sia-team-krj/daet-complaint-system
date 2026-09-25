<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
    }

    public function test_admin_can_search_and_filter_the_complete_account_directory(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'first_name' => 'Engineering',
            'last_name' => 'Operator',
        ]);
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Resident',
            'last_name' => 'Directory',
            'email' => 'resident.directory@example.com',
        ]);
        $inactive = User::factory()->create([
            'role' => 'citizen',
            'is_active' => false,
            'first_name' => 'Paused',
            'last_name' => 'Resident',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index', [
                'search' => 'directory',
                'role' => 'citizen',
                'status' => 'active',
            ]));

        $response
            ->assertOk()
            ->assertSee('User management')
            ->assertSee($resident->full_name)
            ->assertSee($resident->email)
            ->assertDontSee($staff->full_name)
            ->assertDontSee($inactive->full_name);
    }

    public function test_non_admin_users_cannot_access_or_mutate_the_user_directory(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($staff)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($staff)
            ->patch(route('admin.users.update', $resident), [
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'role' => 'citizen',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('citizen', $resident->fresh()->role);
    }

    public function test_admin_can_update_profile_role_and_department_and_audit_each_change(): void
    {
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Before',
            'last_name' => 'Name',
            'email' => 'before.name@example.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $resident), [
                'first_name' => 'After',
                'last_name' => 'Name',
                'email' => 'after.name@example.com',
                'contact_number' => '09171234567',
                'barangay' => User::BARANGAYS[0],
                'role' => 'staff',
                'department_id' => $this->department->id,
            ]);

        $response->assertRedirect(route('admin.users.show', $resident));
        $resident->refresh();

        $this->assertSame('After', $resident->first_name);
        $this->assertSame('after.name@example.com', $resident->email);
        $this->assertSame('staff', $resident->role);
        $this->assertSame($this->department->id, $resident->department_id);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.profile_updated',
            'actor_id' => $this->admin->id,
            'subject_id' => $resident->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.role_updated',
            'actor_id' => $this->admin->id,
            'subject_id' => $resident->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.department_updated',
            'actor_id' => $this->admin->id,
            'subject_id' => $resident->id,
        ]);
    }

    public function test_staff_role_requires_an_active_department(): void
    {
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $resident), [
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'role' => 'staff',
                'department_id' => null,
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertSame('citizen', $resident->fresh()->role);
    }

    public function test_staff_with_assigned_complaints_cannot_change_role_until_reassigned(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);
        Complaint::factory()->create([
            'assigned_staff_id' => $staff->id,
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $staff), [
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'email' => $staff->email,
                'role' => 'citizen',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('staff', $staff->fresh()->role);
    }

    public function test_admin_cannot_change_or_pause_their_own_access(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $this->admin), [
                'first_name' => $this->admin->first_name,
                'last_name' => $this->admin->last_name,
                'email' => $this->admin->email,
                'role' => 'staff',
                'department_id' => $this->department->id,
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle', $this->admin))
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_can_pause_and_reactivate_a_staff_account_with_audit_records(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle', $staff))
            ->assertRedirect();

        $this->assertFalse($staff->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.deactivated',
            'actor_id' => $this->admin->id,
            'subject_id' => $staff->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle', $staff))
            ->assertRedirect();

        $this->assertTrue($staff->fresh()->is_active);
        $this->assertSame(2, ActivityLog::where('subject_id', $staff->id)
            ->whereIn('action', ['user.deactivated', 'user.activated'])
            ->count());
    }

    public function test_user_detail_page_contains_admin_only_identity_and_account_controls(): void
    {
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Private',
            'last_name' => 'Resident',
            'email' => 'private.resident@example.com',
            'contact_number' => '09171234567',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $resident))
            ->assertOk()
            ->assertSee('Account identity')
            ->assertSee('Private Resident')
            ->assertSee('private.resident@example.com')
            ->assertSee('09171234567')
            ->assertSee('Access control')
            ->assertSee('Account activity');
    }
}
