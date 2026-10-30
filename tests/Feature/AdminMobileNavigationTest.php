<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mobile_navigation_keeps_the_workspace_drawer_separate_from_the_account_popover(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-sidebar-toggle', false)
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('Overview', false)
            ->assertSee('All complaints', false)
            ->assertSee('User management', false)
            ->assertSee('Staff invitations', false)
            ->assertSee('id="mobile-user-menu-btn"', false)
            ->assertSee('id="mobile-user-dropdown"', false)
            ->assertSee('aria-controls="mobile-user-dropdown"', false)
            ->assertSee('class="nav-dropdown nav-dropdown--mobile"', false)
            ->assertDontSee('id="mobile-menu-btn"', false);
    }

    public function test_file_a_complaint_cta_is_limited_to_citizens(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $cta = route('complaints.create');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);

        $citizen = User::factory()->create([
            'role' => 'citizen',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee($cta);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertDontSee($cta);

        $this->actingAs($citizen)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($cta);
    }

    public function test_operator_dashboard_is_reached_through_the_logo_instead_of_a_navbar_link(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('admin.dashboard') . '" class="navbar-logo', false)
            ->assertSee('href="' . route('admin.dashboard') . '" class="dropdown-item"', false)
            ->assertDontSee('class="nav-link">Admin Dashboard', false);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('staff.dashboard') . '" class="navbar-logo', false)
            ->assertDontSee('class="nav-link">Staff Dashboard', false)
            ->assertDontSee('href="' . route('admin.dashboard') . '" class="dropdown-item"', false);
    }

    public function test_account_menu_orders_dashboard_then_complaints_account_and_sign_out(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $start = strpos($content, 'id="user-dropdown"');
        $end = strpos($content, 'class="nav-mobile-right"');
        $this->assertIsInt($start);
        $this->assertIsInt($end);
        $menu = substr($content, $start, $end - $start);

        $dashboard = strpos($menu, 'Admin Dashboard');
        $complaints = strpos($menu, 'My Complaints');
        $account = strpos($menu, 'My Account');
        $signOut = strpos($menu, 'Sign Out');

        $this->assertIsInt($dashboard);
        $this->assertIsInt($complaints);
        $this->assertIsInt($account);
        $this->assertIsInt($signOut);

        $this->assertLessThan($complaints, $dashboard, 'Admin Dashboard must be the first menu item.');
        $this->assertLessThan($account, $complaints, 'My Complaints must follow Admin Dashboard.');
        $this->assertLessThan($signOut, $account, 'My Account must follow My Complaints.');
        $this->assertStringContainsString('dropdown-logout', $menu, 'Sign Out must stay visually separated.');
    }
}
