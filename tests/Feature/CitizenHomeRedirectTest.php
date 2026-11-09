<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The citizen dashboard is merged into My Complaints: /dashboard stays a named
 * route but 302s citizens to complaints.index. Staff and admin keep landing on
 * their own workspaces, guests still meet the login screen.
 */
class CitizenHomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    private function citizen(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'citizen',
            'email_verified_at' => now(),
        ], $attributes));
    }

    private function staff(): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => Department::where('code', 'ENG')->firstOrFail()->id,
            'email_verified_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_behaviour_is_unchanged(): void
    {
        $this->get('/')->assertOk();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/complaints')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
    }

    public function test_citizen_dashboard_redirects_to_my_complaints_without_a_loop(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->get('/dashboard')
            ->assertStatus(302)
            ->assertRedirect(route('complaints.index'));

        // The destination itself must not bounce back to /dashboard.
        $this->actingAs($citizen)
            ->get(route('complaints.index'))
            ->assertOk();

        $this->actingAs($citizen)
            ->get('/')
            ->assertRedirect(route('complaints.index'));
    }

    public function test_citizen_dashboard_redirect_preserves_the_query_string(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->get('/dashboard?foo=1&status=open')
            ->assertStatus(302)
            ->assertRedirect(route('complaints.index', ['foo' => '1', 'status' => 'open']));
    }

    public function test_staff_and_admin_dashboard_routes_are_unchanged(): void
    {
        $staff = $this->staff();
        $admin = $this->admin();

        $this->actingAs($staff)->get('/dashboard')->assertRedirect(route('staff.dashboard'));
        $this->actingAs($staff)->get('/')->assertRedirect(route('staff.dashboard'));

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));
    }

    public function test_unverified_citizen_still_goes_to_verification(): void
    {
        $citizen = $this->citizen(['email_verified_at' => null]);

        $this->actingAs($citizen)
            ->get('/dashboard')
            ->assertRedirect(route('email.verify'));
    }

    public function test_citizen_post_login_lands_on_my_complaints(): void
    {
        $citizen = $this->citizen(['email' => 'login.citizen@example.com']);

        $this->post('/login', [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect(route('complaints.index'));
    }

    public function test_citizen_post_login_respects_the_intended_url(): void
    {
        $citizen = $this->citizen(['email' => 'intended.citizen@example.com']);

        $this->get('/complaints?status=open')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect('/complaints?status=open');
    }

    public function test_staff_post_login_keeps_the_historical_dashboard_redirect(): void
    {
        $staff = $this->staff();
        $staff->forceFill(['email' => 'login.staff@example.com'])->save();

        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_admin_post_login_keeps_the_historical_dashboard_redirect(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['email' => 'login.admin@example.com'])->save();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_citizen_cannot_reach_operator_workspaces(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($citizen)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('dashboard'));
    }
}
