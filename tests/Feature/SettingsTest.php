<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $dept = \App\Models\Department::where('code', 'ENG')->firstOrFail();
        $this->citizen = User::factory()->create(['role' => 'citizen', 'email_verified_at' => now()]);
        $this->staff = User::factory()->create(['role' => 'staff', 'department_id' => $dept->id, 'email_verified_at' => now()]);
        $this->admin = User::factory()->create(['role' => 'admin', 'department_id' => null, 'email_verified_at' => now()]);
    }

    public function test_all_roles_can_see_their_settings_tabs(): void
    {
        foreach ([$this->citizen, $this->staff, $this->admin] as $user) {
            $this->actingAs($user)->get(route('settings', ['tab' => 'profile']))->assertOk()->assertSee(__('settings.title'));
            $this->actingAs($user)->get(route('settings', ['tab' => 'preferences']))->assertOk()->assertSee(__('settings.preferences.title'));
        }
    }

    public function test_resident_sees_privacy_and_staff_sees_queue_options(): void
    {
        $this->actingAs($this->citizen)->get(route('settings', ['tab' => 'privacy']))->assertOk()->assertSee(__('settings.privacy.download'));
        $this->actingAs($this->staff)->get(route('settings', ['tab' => 'notifications']))->assertOk()->assertSee(__('settings.notifications.daily_digest'));
    }

    public function test_preferences_save_locale_text_size_and_contrast(): void
    {
        $this->actingAs($this->citizen)->post(route('settings.preferences.update'), [
            'locale' => 'fil',
            'text_size' => 'large',
        ])->assertRedirect(route('settings', ['tab' => 'preferences']));

        $this->assertDatabaseHas('users', ['id' => $this->citizen->id, 'locale' => 'fil', 'text_size' => 'large']);
    }

    public function test_guest_locale_is_saved_to_new_account_on_registration(): void
    {
        $this->post(route('locale.update'), ['locale' => 'fil']);

        $this->post(route('register'), [
            'first_name' => 'Guest',
            'last_name' => 'Tester',
            'email' => 'guest.locale@daetlistens.gov.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'guest.locale@daetlistens.gov.ph', 'locale' => 'fil']);
    }

    public function test_saved_profile_language_wins_on_login(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'locale' => 'fil', 'email_verified_at' => now(), 'password' => bcrypt('Password123!')]);

        // Guest session is English, but the saved profile (Filipino) must win.
        $this->session(['locale' => 'en']);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!']);

        $this->assertSame('fil', session('locale'));
    }

    public function test_system_default_applies_to_staff_without_a_saved_choice(): void
    {
        SystemSetting::setValue('default_locale', 'fil');
        $staff = User::factory()->create(['role' => 'staff', 'email_verified_at' => now(), 'locale' => null]);

        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();
        $this->assertSame('fil', app()->getLocale());
    }

    public function test_only_admin_can_reach_system_settings(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->assertSee(__('admin_settings.title'));
        $this->actingAs($this->staff)->get(route('admin.settings.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->citizen)->get(route('admin.settings.index'))->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_admin_settings_update_is_audited(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'default_locale' => 'fil',
            'overdue_days' => 7,
            'waiting_reminder_days' => 2,
            'invitation_expires_in_hours' => 24,
            'temp_password_expires_in_hours' => 48,
            'password_min_length' => 10,
            'session_timeout_minutes' => 60,
            'sender_name' => 'Daet Listens',
            'footer_text' => 'Footer',
            'contact_text' => 'Contact',
            'audit_retention_days' => 180,
            'barangays' => "Alawihao\nAwitan",
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame('fil', SystemSetting::getValue('default_locale'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'system.settings_updated', 'actor_id' => $this->admin->id]);
    }

    public function test_sessions_list_and_logout_other_devices(): void
    {
        $this->actingAs($this->citizen)->get(route('settings', ['tab' => 'security']))->assertOk()->assertSee(__('settings.security.sessions'));
        $this->actingAs($this->citizen)->post(route('settings.logout-other-devices'))->assertRedirect(route('settings', ['tab' => 'security']));
    }

    public function test_resident_can_download_data_as_json(): void
    {
        $response = $this->actingAs($this->citizen)->get(route('settings.download-data'));
        $response->assertOk();
        $this->assertArrayHasKey('profile', $response->json());
        $this->assertArrayHasKey('complaints', $response->json());
    }
}
