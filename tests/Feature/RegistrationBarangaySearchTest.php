<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationBarangaySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_uses_a_searchable_barangay_field_with_suggestions(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-barangay-search', false)
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-autocomplete="list"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('overflow-anchor: none', false)
            ->assertSee('justify-content: flex-start', false)
            // The suggestion list must paint above the field-groups that follow
            // it: every .fu group keeps its fadeUp transform, which turns each
            // one into a stacking context, and the groups after the barangay
            // field otherwise cover the list so only the first suggestion
            // stayed clickable.
            ->assertSee('field-group.has-suggestions', false)
            ->assertSee('z-index: 30', false)
            ->assertSee('Barangay I (Hilahod)')
            ->assertSee('Barangay VI (Centro)')
            ->assertDontSee('<select id="barangay"', false);
    }

    public function test_registration_accepts_an_exact_barangay_name(): void
    {
        Mail::fake();

        $this->post(route('register'), [
            'first_name' => 'Barangay',
            'last_name' => 'Tester',
            'email' => 'barangay.tester@daetlistens.gov.ph',
            'barangay' => 'Gubat (Moreno, Gubat, Mandulongan)',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('email.verify'));

        $this->assertDatabaseHas('users', [
            'email' => 'barangay.tester@daetlistens.gov.ph',
            'barangay' => 'Gubat (Moreno, Gubat, Mandulongan)',
        ]);
    }

    public function test_registration_rejects_a_partial_barangay_name(): void
    {
        $this->post(route('register'), [
            'first_name' => 'Barangay',
            'last_name' => 'Tester',
            'email' => 'barangay.tester@daetlistens.gov.ph',
            'barangay' => 'Barangay',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertSessionHasErrors('barangay');

        $this->assertGuest();
    }
}
