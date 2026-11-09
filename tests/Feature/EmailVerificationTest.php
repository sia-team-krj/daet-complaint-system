<?php

namespace Tests\Feature;

use App\Mail\EmailOtp;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\BrevoOtpSender;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_registration_requires_email_verification_before_dashboard_access(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Otp',
            'last_name' => 'Tester',
            'email' => 'otp.tester@daetlistens.gov.ph',
            'barangay' => User::BARANGAYS[0],
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('email.verify'));

        $user = User::where('email', 'otp.tester@daetlistens.gov.ph')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());

        $this->get(route('dashboard'))->assertRedirect(route('email.verify'));
        $this->get(route('email.verify'))
            ->assertOk()
            ->assertSee('Verify your email')
            ->assertSee('Send a new code');

        $code = $this->captureOtp($user->id, 'registration');
        $verification = EmailVerificationCode::where('user_id', $user->id)
            ->where('purpose', 'registration')
            ->sole();

        $this->assertNotSame($code, $verification->code_hash);
        $this->assertTrue($verification->expires_at->isFuture());

        $wrongCode = $code === '000000' ? '000001' : '000000';
        $this->post(route('email.verify.submit'), [
            'purpose' => 'registration',
            'code' => $wrongCode,
        ])->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $this->post(route('email.verify.submit'), [
            'purpose' => 'registration',
            'code' => $code,
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($verification->fresh()->consumed_at);
    }

    public function test_otp_uses_brevo_api_when_a_key_is_configured(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.from_email' => 'no-reply@daetlistens.gov.ph',
        ]);

        Http::fake([
            '*' => Http::response(['messageId' => 'test-message-id'], 201),
        ]);

        $this->post(route('register'), [
            'first_name' => 'Brevo',
            'last_name' => 'Tester',
            'email' => 'brevo.tester@daetlistens.gov.ph',
            'barangay' => User::BARANGAYS[0],
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('email.verify'));

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && ($request['to'][0]['email'] ?? null) === 'brevo.tester@daetlistens.gov.ph'
                && is_string($request['htmlContent'] ?? null)
                && str_contains($request['htmlContent'], 'verification code');
        });
        Mail::assertNothingSent();
    }

    public function test_resending_replaces_the_active_registration_code(): void
    {
        $this->post(route('register'), [
            'first_name' => 'Resend',
            'last_name' => 'Tester',
            'email' => 'resend.tester@daetlistens.gov.ph',
            'barangay' => User::BARANGAYS[0],
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('email.verify'));

        $user = User::where('email', 'resend.tester@daetlistens.gov.ph')->sole();
        $firstCode = $this->captureOtp($user->id, 'registration');

        $this->post(route('email.verify.resend'), [
            'purpose' => 'registration',
        ])->assertRedirect(route('email.verify', ['purpose' => 'registration']));

        $secondCode = $this->captureOtp($user->id, 'registration');
        $this->assertNotSame($firstCode, $secondCode);

        $this->post(route('email.verify.submit'), [
            'purpose' => 'registration',
            'code' => $firstCode,
        ])->assertSessionHasErrors('code');

        $this->post(route('email.verify.submit'), [
            'purpose' => 'registration',
            'code' => $secondCode,
        ])->assertRedirect(route('complaints.index'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_second_code_request_inside_the_cooldown_is_refused(): void
    {
        config(['email-verification.resend_cooldown_seconds' => 60]);

        $user = $this->registerUnverifiedUser('cooldown.tester');

        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertRedirect(route('email.verify', ['purpose' => 'registration']))
            ->assertSessionHas('status');

        // Registration issued one and the resend issued a second.
        $this->assertSame(2, $this->issuedCount($user->id));

        // Straight back again, inside the 60s window.
        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertRedirect(route('email.verify', ['purpose' => 'registration']))
            ->assertSessionHas('error')
            ->assertSessionMissing('status');

        // The refused request must not have issued a code.
        $this->assertSame(2, $this->issuedCount($user->id));
    }

    public function test_the_cooldown_refusal_reports_the_real_time_remaining(): void
    {
        config(['email-verification.resend_cooldown_seconds' => 60]);

        $this->registerUnverifiedUser('remaining.tester');

        $this->post(route('email.verify.resend'), ['purpose' => 'registration']);

        $this->travel(20)->seconds();

        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertSessionHas('error');

        // 40s left, not the full 60 — the message must not tell someone to wait
        // longer than they actually have to.
        $this->assertStringContainsString('40', (string) session('error'));
    }

    public function test_the_verify_page_shows_a_countdown_and_disables_resending(): void
    {
        config(['email-verification.resend_cooldown_seconds' => 60]);

        $this->registerUnverifiedUser('countdown.tester');

        $this->post(route('email.verify.resend'), ['purpose' => 'registration']);

        $this->travel(30)->seconds();

        $this->get(route('email.verify'))
            ->assertOk()
            ->assertSee('data-resend-button', false)
            // The server renders the true remaining time, so a reload resumes
            // the real cooldown instead of restarting a client-side guess.
            ->assertSee('data-remaining="30"', false);
    }

    public function test_resending_is_allowed_again_once_the_cooldown_expires(): void
    {
        config(['email-verification.resend_cooldown_seconds' => 60]);

        $user = $this->registerUnverifiedUser('expiry.tester');

        $this->post(route('email.verify.resend'), ['purpose' => 'registration']);
        $this->assertSame(2, $this->issuedCount($user->id));

        $this->travel(61)->seconds();

        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertSessionHas('status');

        $this->assertSame(3, $this->issuedCount($user->id));
    }

    public function test_resending_is_capped_over_a_longer_window(): void
    {
        config([
            'email-verification.resend_cooldown_seconds' => 1,
            'email-verification.resend_max_per_hour' => 3,
        ]);

        $user = $this->registerUnverifiedUser('capped.tester');

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
                ->assertSessionHas('status');

            $this->travel(2)->seconds();
        }

        // Registration plus three resends.
        $this->assertSame(4, $this->issuedCount($user->id));

        // The cooldown has long expired, but the hourly ceiling still holds.
        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertSessionHas('error')
            ->assertSessionMissing('status');

        $this->assertSame(4, $this->issuedCount($user->id));
    }

    public function test_a_failed_send_still_consumes_the_cooldown(): void
    {
        config(['email-verification.resend_cooldown_seconds' => 60]);

        $user = $this->registerUnverifiedUser('failed.tester');

        // The attempt is counted, not the delivery. A broken mail transport must
        // not be retryable without a pause, or this page becomes a way to hammer
        // a provider that is already failing.
        $this->app->instance(EmailVerificationService::class, new class($this->app->make(BrevoOtpSender::class)) extends EmailVerificationService
        {
            public function issue(User $user, string $email, string $purpose): EmailVerificationCode
            {
                throw new RuntimeException('transport down');
            }
        });

        $this->post(route('email.verify.resend'), ['purpose' => 'registration'])
            ->assertSessionHas('error');

        // Asserted on the limiter rather than by sending a second request.
        // Route::$controller is memoised for the life of the app, so a second
        // request in this test would reuse the very service that just threw and
        // would appear "blocked" for the wrong reason.
        $this->assertGreaterThan(
            0,
            RateLimiter::availableIn("email-otp:resend:{$user->id}:registration"),
            'A send that failed must still have consumed the resend cooldown.',
        );
    }

    public function test_notification_poll_does_not_poison_post_verification_redirect(): void
    {
        $user = $this->registerUnverifiedUser('poll.tester');
        $code = $this->captureOtp($user->id, 'registration');

        // The navbar bell polls this endpoint via fetch while unverified.
        // Before the fix, this stored the JSON endpoint as url.intended,
        // so verifying dumped the user on a pretty-printed badge payload.
        $this->get(route('notifications.unread-count'), ['Accept' => 'application/json'])
            ->assertRedirect(route('email.verify'));
        $this->assertNotSame(route('notifications.unread-count'), session('url.intended'));

        // Even a session already poisoned by the old behaviour must recover.
        $this->withSession(['url.intended' => route('notifications.unread-count')]);
        $this->post(route('email.verify.submit'), [
            'purpose' => 'registration',
            'code' => $code,
        ])->assertRedirect(route('complaints.index'));
    }

    private function registerUnverifiedUser(string $localPart): User
    {
        $email = "{$localPart}@daetlistens.gov.ph";

        $this->post(route('register'), [
            'first_name' => 'Cooldown',
            'last_name' => 'Tester',
            'email' => $email,
            'barangay' => User::BARANGAYS[0],
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('email.verify'));

        return User::where('email', $email)->sole();
    }

    /**
     * Every code ever issued, consumed or not.
     *
     * Counting only unconsumed rows would be useless here: issuing a new code
     * consumes the previous one, so that number never rises above one however
     * many times a code is sent.
     */
    private function issuedCount(int $userId): int
    {
        return EmailVerificationCode::query()
            ->where('user_id', $userId)
            ->count();
    }

    public function test_registered_user_can_verify_a_new_email_before_it_replaces_the_current_email(): void
    {
        $user = User::factory()->create([
            'email' => 'current.tester@daetlistens.gov.ph',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'changed.tester@daetlistens.gov.ph',
            'current_password' => 'password',
        ]);

        $response->assertRedirect(route('email.verify'));
        $this->assertSame('current.tester@daetlistens.gov.ph', $user->fresh()->email);

        $verification = EmailVerificationCode::where('user_id', $user->id)
            ->where('purpose', 'email_change')
            ->sole();
        $code = $this->captureOtp($user->id, 'email_change');

        $this->post(route('email.verify.submit'), [
            'purpose' => 'email_change',
            'code' => $code,
        ])->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertSame('changed.tester@daetlistens.gov.ph', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($verification->fresh()->consumed_at);
    }

    public function test_email_change_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'protected.tester@daetlistens.gov.ph',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => 'new.protected.tester@daetlistens.gov.ph',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('protected.tester@daetlistens.gov.ph', $user->fresh()->email);
        $this->assertDatabaseCount('email_verification_codes', 0);
    }

    private function captureOtp(int $userId, string $purpose): string
    {
        $code = null;

        Mail::assertSent(EmailOtp::class, function (EmailOtp $mail) use (&$code, $userId, $purpose): bool {
            if ($mail->user->id !== $userId || $mail->purpose !== $purpose) {
                return false;
            }

            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }
}
