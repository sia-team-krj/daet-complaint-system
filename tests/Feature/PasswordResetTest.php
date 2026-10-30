<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_link_and_preserves_the_email_on_the_reset_form(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset.tester@daetlistens.gov.ph',
            'password' => Hash::make('OldPassword1!'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');

        $notification = null;
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $sent) use (&$notification): bool {
                $notification = $sent;

                return true;
            },
        );

        $this->assertNotNull($notification);
        $token = basename((string) parse_url($notification->url, PHP_URL_PATH));
        parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
        $this->assertSame($user->email, $query['email'] ?? null);
        $this->assertNotEmpty($token);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Set New Password')
            ->assertSee($user->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
    }

    public function test_forgot_password_uses_brevo_when_a_key_is_configured(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.from_email' => 'no-reply@daetlistens.gov.ph',
        ]);

        Http::fake([
            '*' => Http::response(['messageId' => 'test-message-id'], 201),
        ]);
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'brevo-reset.tester@daetlistens.gov.ph',
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');

        Http::assertSent(function (Request $request) use ($user): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && ($request['to'][0]['email'] ?? null) === $user->email
                && ($request['subject'] ?? null) === 'Reset your Daet Listens password'
                && is_string($request['htmlContent'] ?? null)
                && str_contains($request['htmlContent'], 'Reset My Password');
        });
        Notification::assertNothingSent();
    }

    public function test_forgot_password_does_not_reveal_whether_an_email_exists(): void
    {
        $this->post(route('password.email'), [
            'email' => 'missing.tester@daetlistens.gov.ph',
        ])
            ->assertSessionHas('status')
            ->assertSessionMissing('errors');
    }
}
