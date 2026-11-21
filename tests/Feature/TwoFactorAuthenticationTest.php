<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_setup_asks_for_the_password_before_showing_the_secret(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.two-factor.show'))
            ->assertOk()
            ->assertSee(__('settings.two_factor.start_title'))
            ->assertDontSee('<dialog', false)
            ->assertDontSee('otpauth://');

        $this->actingAs($admin)
            ->post(route('settings.two-factor.start'), [
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertNull($admin->fresh()->two_factor_secret);

        $this->actingAs($admin)
            ->post(route('settings.two-factor.start'), [
                'current_password' => 'password',
            ])
            ->assertRedirect(route('settings.two-factor.show'))
            ->assertSessionHas('2fa.setup_authorized', true);

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);

        $this->actingAs($admin)
            ->get(route('settings.two-factor.show'))
            ->assertOk()
            ->assertSee($admin->two_factor_secret)
            ->assertSee('<svg', false);
    }

    public function test_settings_security_two_factor_cta_navigates_to_the_setup_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings', ['tab' => 'security']))
            ->assertOk()
            ->assertDontSee('two-factor-auth-dialog')
            ->assertDontSee('data-two-factor-dialog', false)
            ->assertDontSee('data-close-two-factor-dialog', false)
            ->assertSee('settings/security/two-factor', false)
            ->assertSee(__('settings.two_factor.setup_cta'));
    }

    public function test_admin_can_enable_two_factor_and_receives_recovery_codes_once(): void
    {
        Carbon::setTestNow('2026-11-21 12:00:00');

        $admin = User::factory()->create(['role' => 'admin']);
        $secret = app(TwoFactorAuthentication::class)->generateSecret();

        $admin->forceFill([
            'two_factor_secret' => $secret,
        ])->save();

        $response = $this->actingAs($admin)
            ->withSession(['2fa.setup_authorized' => true])
            ->post(route('settings.two-factor.enable'), [
                'code' => $this->currentCode($secret),
            ]);

        $response->assertRedirect(route('settings.two-factor.show'))
            ->assertSessionHas('status', __('settings.two_factor.enabled'))
            ->assertSessionHas('two_factor_recovery_codes');

        $admin->refresh();

        $this->assertNotNull($admin->two_factor_confirmed_at);
        $this->assertCount(8, $admin->two_factor_recovery_codes);
        $this->assertTrue($this->sessionHasPassed($admin));

        $codes = session('two_factor_recovery_codes');
        $this->assertCount(8, $codes);

        $this->actingAs($admin)
            ->get(route('settings.two-factor.show'))
            ->assertOk()
            ->assertSee($codes[0])
            ->assertDontSee(__('settings.two_factor.manage'))
            ->assertSee(__('settings.two_factor.back_to_settings'))
            ->assertSee(__('settings.two_factor.replace_authenticator'));
    }

    public function test_settings_security_shows_disable_form_when_two_factor_is_enabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->get(route('settings', ['tab' => 'security']))
            ->assertOk()
            ->assertSee(__('settings.two_factor.disable'))
            ->assertSee('id="two-factor-disable-password"', false)
            ->assertSee('action="'.route('settings.two-factor.disable').'"', false);
    }

    public function test_admin_can_disable_two_factor_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa.passed_at' => now()->timestamp])
            ->post(route('settings.two-factor.disable'), [
                'current_password' => 'password',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionHas('status', __('settings.two_factor.disabled'));

        $admin->refresh();

        $this->assertFalse($admin->hasTwoFactorEnabled());
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_last_used_step);
        $this->assertFalse($this->app['session.store']->has('2fa.passed_at'));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'auth.2fa.disabled',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_disable_requires_the_current_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->post(route('settings.two-factor.disable'), [
                'current_password' => 'not-the-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_login_sends_an_enrolled_admin_to_the_challenge(): void
    {
        Carbon::setTestNow('2026-11-21 12:00:00');
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $secret = $this->enableTwoFactor($admin);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('2fa.pending', true);

        $this->assertAuthenticatedAs($admin);

        $this->post(route('two-factor.verify'), [
            'code' => $this->currentCode($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($this->sessionHasPassed($admin));
    }

    public function test_admin_without_two_factor_is_sent_to_setup(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('settings.two-factor.show'))
            ->assertSessionHas('error', __('settings.two_factor.setup_required'));
    }

    public function test_admin_access_to_the_staff_workspace_is_gated_too(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('settings.two-factor.show'));
    }

    public function test_enrolled_admin_without_a_passed_challenge_is_sent_to_the_challenge(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('two-factor.challenge'));
    }

    public function test_admin_with_a_passed_challenge_can_open_the_workspace(): void
    {
        $this->seed(DepartmentSeeder::class);
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa.passed_at' => now()->timestamp])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_recovery_code_is_single_use(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $code = 'ABCDE-FGHIJ';
        $admin->forceFill([
            'two_factor_recovery_codes' => [Hash::make($code)],
        ])->save();

        $this->actingAs($admin)
            ->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertCount(0, $admin->fresh()->two_factor_recovery_codes);

        // A fresh challenge (a new login session) must not accept it again.
        $this->actingAs($admin)
            ->withSession(['2fa.passed_at' => null, '2fa.failed' => 0])
            ->post(route('two-factor.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_a_totp_code_cannot_be_replayed(): void
    {
        Carbon::setTestNow('2026-11-21 12:00:00');
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $secret = $this->enableTwoFactor($admin);
        $code = $this->currentCode($secret);

        $this->actingAs($admin)
            ->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->withSession(['2fa.passed_at' => null, '2fa.failed' => 0])
            ->post(route('two-factor.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_cli_reset_clears_two_factor_for_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->artisan('admin:reset-2fa', [
            'email' => $admin->email,
            '--force' => true,
        ])->assertSuccessful();

        $admin->refresh();

        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_last_used_step);
    }

    public function test_non_admin_cannot_reach_two_factor_setup(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get(route('settings.two-factor.show'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_setup_route_remains_reachable_while_enforcement_is_on(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.two-factor.show'))
            ->assertOk();
    }

    public function test_login_clears_a_stale_passed_challenge_from_the_session(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->withSession(['2fa.passed_at' => now()->timestamp])
            ->post(route('login'), [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionMissing('2fa.passed_at');
    }

    public function test_five_failed_challenges_end_the_session(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post(route('two-factor.verify'), ['code' => 'not-a-code'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('two-factor.verify'), ['code' => 'not-a-code'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_changing_the_password_clears_the_passed_challenge(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa.passed_at' => now()->timestamp])
            ->post(route('settings.password.update'), [
                'current_password' => 'password',
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('settings', ['tab' => 'security']))
            ->assertSessionMissing('2fa.passed_at');

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('two-factor.challenge'));
    }

    public function test_challenge_page_renders_for_an_enrolled_admin(): void
    {
        config(['admin.two_factor.required' => true]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertSee(__('settings.two_factor.challenge_title'))
            ->assertSee(__('settings.two_factor.verify'));
    }

    private function enableTwoFactor(User $user): string
    {
        $secret = app(TwoFactorAuthentication::class)->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
            'two_factor_last_used_step' => null,
        ])->save();

        return $secret;
    }

    private function currentCode(string $secret): string
    {
        $google2fa = app(Google2FA::class);

        return $google2fa->oathTotp($secret, $google2fa->getTimestamp());
    }

    private function sessionHasPassed(User $user): bool
    {
        return $this->app['session.store']->has('2fa.passed_at');
    }
}
