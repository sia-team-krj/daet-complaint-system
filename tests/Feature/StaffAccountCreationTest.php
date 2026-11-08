<?php

namespace Tests\Feature;

use App\Mail\StaffInvitationMail;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'email' => 'new.staff@example.com',
            'contact_number' => '09171234567',
            'department_id' => $this->department->id,
            'creation_method' => 'emailed_temp_password',
        ], $overrides);
    }

    public function test_create_page_shows_method_cards_and_no_invitation_button(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('How should this account get its password?')
            ->assertSee('Send invitation link')
            ->assertSee('Email a temporary password')
            ->assertSee('Set a password manually (no email sent)')
            ->assertSee('Invitation expires in', false)
            ->assertSee('Generate strong password')
            ->assertSee('Share this password securely. It will not be shown again after you create the account.')
            ->assertDontSee('Use invitation instead');
    }

    public function test_non_admin_cannot_use_the_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'department_id' => $this->department->id, 'email_verified_at' => now()]);

        $this->actingAs($staff)->get(route('admin.staff.create'))->assertRedirect(route('dashboard'));
        $this->actingAs($staff)->post(route('admin.staff.store'), $this->payload())->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);
    }

    public function test_option_a_creates_invitation_and_emails_it_without_a_user(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->payload([
                'creation_method' => 'invitation',
                'invitation_expires_in_hours' => 48,
            ]))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);
        $invitation = InvitationCode::sole();
        $this->assertSame('new.staff@example.com', $invitation->invitee_email);
        $this->assertSame($this->department->id, $invitation->department_id);
        $this->assertTrue($invitation->expires_at->between(now()->addHours(47), now()->addHours(49)));
        Mail::assertSent(StaffInvitationMail::class, fn (StaffInvitationMail $mail) => $mail->hasTo('new.staff@example.com'));

        $log = ActivityLog::where('action', 'staff.created')->sole();
        $this->assertSame($this->admin->id, $log->actor_id);
        $this->assertSame('invitation', $log->metadata['method']);
        $this->assertSame(48, $log->metadata['expires_in_hours']);
        $this->assertStringNotContainsString('password', strtolower((string) json_encode($log->metadata)));
    }

    public function test_option_b_creates_user_with_hashed_temp_password_and_must_change(): void
    {
        Notification::fake();
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->payload())
            ->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'new.staff@example.com')->sole();
        $this->assertTrue($staff->must_change_password);
        $this->assertNotNull($staff->password_expires_at);
        $this->assertTrue($staff->password_expires_at->between(now()->addHours(71), now()->addHours(73)));
        $this->assertNotSame('password', $staff->password);
        $this->assertStringStartsWith('$2y$', $staff->password);

        Notification::assertSentTo($staff, StaffWelcomeNotification::class);

        $log = ActivityLog::where('action', 'staff.created')->where('subject_id', $staff->id)->sole();
        $this->assertSame('emailed_temp_password', $log->metadata['method']);
        $this->assertSame($this->admin->id, $log->metadata['created_by']);
        $this->assertStringNotContainsString($staff->password, (string) $log->description);
        $this->assertStringNotContainsString($staff->password, (string) json_encode($log->metadata));
    }

    public function test_option_c_creates_user_without_any_email_and_shows_password_once(): void
    {
        Notification::fake();
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->payload([
                'creation_method' => 'manual_password',
                'temporary_password' => 'ManualPass123',
                'temporary_password_confirmation' => 'ManualPass123',
            ]));

        $response->assertRedirect(route('admin.staff.created'));
        $response->assertSessionHas('staff_created_once');

        Notification::assertNothingSent();
        Mail::assertNothingSent();

        $staff = User::where('email', 'new.staff@example.com')->sole();
        $this->assertTrue(Hash::check('ManualPass123', $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertNotNull($staff->password_expires_at);

        // One-time screen shows the password exactly there, then it is gone.
        $this->actingAs($this->admin)
            ->get(route('admin.staff.created'))
            ->assertOk()
            ->assertSee('new.staff@example.com')
            ->assertSee('ManualPass123')
            ->assertSee('It will not be shown again');

        $this->actingAs($this->admin)
            ->get(route('admin.staff.created'))
            ->assertRedirect(route('admin.staff.index'));

        $log = ActivityLog::where('action', 'staff.created')->where('subject_id', $staff->id)->sole();
        $this->assertSame('manual_password', $log->metadata['method']);
        $this->assertStringNotContainsString('ManualPass123', (string) $log->description);
        $this->assertStringNotContainsString('ManualPass123', (string) json_encode($log->metadata));
    }

    public function test_option_c_rejects_a_weak_or_mismatched_password(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->payload([
                'creation_method' => 'manual_password',
                'temporary_password' => 'weak',
                'temporary_password_confirmation' => 'weak',
            ]))
            ->assertSessionHasErrors('temporary_password');

        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);
    }

    public function test_temp_password_user_is_forced_to_change_on_first_login_and_cannot_login_after_expiry(): void
    {
        Notification::fake();
        Mail::fake();

        $this->actingAs($this->admin)->post(route('admin.staff.store'), $this->payload([
            'creation_method' => 'manual_password',
            'temporary_password' => 'ManualPass123',
            'temporary_password_confirmation' => 'ManualPass123',
        ]));

        $staff = User::where('email', 'new.staff@example.com')->sole();
        $staff->forceFill(['email_verified_at' => now()])->save();

        // Login succeeds but the next page forces a password change.
        // (Logout first: the login route is guest-only, so a still-signed-in
        // admin session would be bounced instead of signing the staff in.)
        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => 'new.staff@example.com',
            'password' => 'ManualPass123',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('password.change.show'));
        $this->get(route('password.change.show'))->assertOk()->assertSee('Set a new password');

        $this->post(route('password.change.store'), [
            'new_password' => 'BrandNewPass123',
            'new_password_confirmation' => 'BrandNewPass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(Hash::check('BrandNewPass123', $staff->fresh()->password));

        // Expired temp passwords can no longer sign in.
        $expired = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'email' => 'expired@example.com',
            'password' => Hash::make('TempPass123'),
            'must_change_password' => true,
            'password_expires_at' => now()->subHour(),
            'email_verified_at' => now(),
        ]);

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => 'expired@example.com',
            'password' => 'TempPass123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
