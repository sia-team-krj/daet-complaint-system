<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Uppercase and lowercase letters')
            ->assertSee('At least one number')
            ->assertSee('data-password-target="password"', false);
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
            ->assertSee('Uppercase and lowercase letters')
            ->assertSee('At least one number')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('id="password"', false);
    }
}
