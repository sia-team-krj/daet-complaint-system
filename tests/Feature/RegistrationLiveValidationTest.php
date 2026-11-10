<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins two contracts of the registration page:
 *
 * 1. The live per-field validation wiring renders (form attribute, inline
 *    error slots, passed-through messages) — and deliberately does NOT exist
 *    for the email field, which stays submit-only so the page cannot be used
 *    as an oracle for whether an address is registered.
 * 2. The server-side messages that can render in the alert are translated
 *    Lang keys in both locales, never raw keys.
 */
class RegistrationLiveValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_form_renders_live_validation_wiring(): void
    {
        $response = $this->get(route('register'))->assertOk();

        $response->assertSee('data-live-validate', false);
        $response->assertSee('data-msg-required', false);
        $response->assertSee('data-msg-phone-regex', false);
        $response->assertSee('data-msg-password-min8', false);
        $response->assertSee('data-msg-password-confirmed', false);
        $response->assertSee('data-msg-terms-accepted', false);

        foreach (['first_name', 'last_name', 'phone', 'barangay', 'password', 'password_confirmation', 'terms'] as $field) {
            $response->assertSee('data-error-for="'.$field.'"', false);
        }

        // The email field must never get an inline validator or error slot.
        $response->assertDontSee('data-error-for="email"', false);
        $response->assertDontSee('data-msg-email', false);
    }

    public function test_duplicate_email_message_is_translated_not_a_raw_key(): void
    {
        $existing = User::factory()->create([
            'email' => 'registration.live.validation@daetlistens.gov.ph',
            'role' => 'citizen',
        ]);

        $this->post(route('register'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => $existing->email,
            'password' => 'S3cret!PassMix',
            'password_confirmation' => 'S3cret!PassMix',
            'terms' => '1',
        ])->assertSessionHasErrors([
            'email' => 'That email address is already registered. Sign in instead, or use a different email.',
        ]);

        $this->assertGuest();
    }

    public function test_live_validation_messages_resolve_in_both_locales(): void
    {
        foreach ([
            'auth.email_unique',
            'auth_ui.register.required',
            'auth_ui.register.first_name_regex',
            'auth_ui.register.last_name_regex',
            'auth_ui.register.phone_regex',
            'auth_ui.register.barangay_invalid',
            'auth_ui.register.password_min_8',
            'auth_ui.register.password_confirmed',
            'auth_ui.register.terms_accepted',
        ] as $key) {
            foreach (['en', 'fil'] as $locale) {
                $value = __($key, [], $locale);
                $this->assertNotSame($key, $value, "{$key} must resolve in {$locale}");
                $this->assertNotSame('', trim((string) $value), "{$key} must not be empty in {$locale}");
            }
        }
    }
}
