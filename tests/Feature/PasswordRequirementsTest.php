<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_shows_live_password_requirements(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertSee('At least 8 characters')
            ->assertSee('At least one uppercase letter')
            ->assertSee('At least one lowercase letter')
            ->assertSee('At least one number')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('data-password-requirement="length"', false)
            ->assertSee('data-password-requirement="uppercase"', false)
            ->assertSee('data-password-requirement="lowercase"', false)
            ->assertDontSee('id="ps-wrap"', false)
            ->assertDontSee('id="ps-label"', false)
            ->assertDontSee('checkStrength', false);
    }

    public function test_password_reset_form_shows_live_password_requirements(): void
    {
        $this->get(route('password.reset', [
            'token' => 'test-reset-token',
            'email' => 'reset.tester@daetlistens.gov.ph',
        ]))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertSee('At least 8 characters')
            ->assertSee('At least one uppercase letter')
            ->assertSee('At least one lowercase letter')
            ->assertSee('At least one number')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('data-password-requirement="length"', false)
            ->assertSee('data-password-requirement="uppercase"', false)
            ->assertSee('data-password-requirement="lowercase"', false);
    }

    public function test_registration_rejects_passwords_that_do_not_meet_all_requirements(): void
    {
        $invalidPasswords = [
            'Short1!',
            'password1',
            'Password!',
            'PASSWORD1',
        ];

        foreach ($invalidPasswords as $index => $password) {
            $this->post(route('register'), [
                'first_name' => 'Password',
                'last_name' => 'Tester',
                'email' => 'password.tester.'.$index.'@daetlistens.gov.ph',
                'password' => $password,
                'password_confirmation' => $password,
                'terms' => '1',
            ])->assertSessionHasErrors('password');

            $this->assertGuest();
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_does_not_reject_a_password_based_on_breach_lookup(): void
    {
        Mail::fake();

        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data): bool
            {
                return false;
            }
        });

        $this->post(route('register'), [
            'first_name' => 'Password',
            'last_name' => 'Tester',
            'email' => 'password.tester@daetlistens.gov.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('email.verify'));

        $this->assertAuthenticated();
        $this->assertNull(Auth::user()->email_verified_at);
        $this->assertDatabaseHas('email_verification_codes', [
            'purpose' => 'registration',
            'consumed_at' => null,
        ]);
    }

    public function test_invitation_form_shows_live_password_requirements(): void
    {
        $this->seed(DepartmentSeeder::class);
        $department = Department::where('code', 'ENG')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $invitation = InvitationCode::create([
            'code' => Str::random(32),
            'department_id' => $department->id,
            'created_by' => $admin->id,
            'expires_at' => now()->addMinutes(10),
            'max_uses' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertSee('At least 8 characters')
            ->assertSee('At least one uppercase letter')
            ->assertSee('At least one lowercase letter')
            ->assertSee('At least one number')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('id="password"', false);
    }
}
