<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_to_filipino_and_the_choice_survives_the_next_request(): void
    {
        $response = $this->post(route('locale.update'), ['locale' => 'fil']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'fil');
        $response->assertCookie(config('localization.cookie'), 'fil');

        $this->get('/')
            ->assertOk()
            ->assertSee('Ang Iyong Tinig.')
            ->assertSee('Paano Ito Gumagana')
            ->assertSee('Apat na Hakbang')
            ->assertSee('Bayan ng Daet')
            ->assertSee('lang="fil"', false);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'bcl'])
            ->assertSessionHasErrors('locale');

        $this->get('/')
            ->assertOk()
            ->assertSee('Your Voice.')
            ->assertSee('lang="en"', false);
    }

    public function test_signed_in_user_locale_is_saved_and_used(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'locale' => 'en',
        ]);

        $this->actingAs($user)
            ->post(route('locale.update'), ['locale' => 'fil'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'fil',
        ]);

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Maghain ng Reklamo')
            ->assertSee('Aking mga Reklamo')
            ->assertSee('lang="fil"', false);
    }

    public function test_saved_user_locale_is_used_without_a_session_choice(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'locale' => 'fil',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Maghain ng Reklamo')
            ->assertSee('lang="fil"', false);
    }

    public function test_invalid_session_locale_falls_back_to_english(): void
    {
        $this->withSession(['locale' => 'bcl'])
            ->get('/')
            ->assertOk()
            ->assertSee('Your Voice.')
            ->assertSee('lang="en"', false);
    }

    public function test_language_switcher_is_visible_on_public_and_auth_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang-switch', false)
            ->assertSee('English')
            ->assertSee('Filipino');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang-switch', false);
    }

    public function test_language_switcher_is_not_shown_to_staff_or_admin_shells(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertDontSee('lang-switch', false);
    }
}
