<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
    }

    public function test_admin_can_generate_a_single_use_invitation_for_a_department(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.invitations.store'), [
                'department_id' => $this->department->id,
            ]);

        $response->assertRedirect(route('admin.invitations.index'));

        $invitation = InvitationCode::sole();

        $this->assertSame($this->department->id, $invitation->department_id);
        $this->assertSame($this->admin->id, $invitation->created_by);
        $this->assertSame(1, $invitation->max_uses);
        $this->assertSame(0, $invitation->used_count);
        $this->assertTrue($invitation->is_active);
        $this->assertTrue($invitation->expires_at->between(now()->addMinutes(9), now()->addMinutes(11)));
    }

    public function test_valid_invitation_creates_one_department_bound_staff_account(): void
    {
        $invitation = $this->createInvitation();

        $response = $this->post(route('invitations.redeem', $invitation->code), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'contact_number' => '09171234567',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticated();

        $staff = User::where('email', 'juan.delacruz@example.com')->sole();

        $this->assertSame('staff', $staff->role);
        $this->assertSame($this->department->id, $staff->department_id);
        $this->assertSame($this->admin->id, $staff->created_by);
        $this->assertTrue($staff->is_active);

        $invitation->refresh();
        $this->assertTrue($invitation->isUsed());
        $this->assertFalse($invitation->is_active);
        $this->assertSame($staff->id, $invitation->redeemed_by);
        $this->assertNotNull($invitation->redeemed_at);
    }

    public function test_invitation_cannot_be_redeemed_more_than_once(): void
    {
        $invitation = $this->createInvitation();

        $this->redeemInvitation($invitation, 'first.staff@example.com');
        Auth::logout();
        $response = $this->redeemInvitation($invitation, 'second.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'second.staff@example.com']);
    }

    public function test_expired_invitation_cannot_create_an_account(): void
    {
        $invitation = $this->createInvitation(['expires_at' => now()->subMinute()]);

        $response = $this->redeemInvitation($invitation, 'expired.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'expired.staff@example.com']);
    }

    public function test_revoked_invitation_cannot_create_an_account(): void
    {
        $invitation = $this->createInvitation(['is_active' => false]);

        $response = $this->redeemInvitation($invitation, 'revoked.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'revoked.staff@example.com']);
    }

    public function test_non_admin_users_cannot_manage_invitations(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.invitations.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_shell_exposes_all_admin_workspaces(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('All complaints')
            ->assertSee('Departments')
            ->assertSee('Staff accounts')
            ->assertSee('Staff invitations')
            ->assertSee('Audit trail');
    }

    public function test_admin_supporting_workspaces_render(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.departments.index'))->assertOk()->assertSee('Add department');
        $this->get(route('admin.invitations.index'))->assertOk()->assertSee('New invitation');
        $this->get(route('admin.audit.index'))->assertOk()->assertSee('Audit trail');
    }

    public function test_staff_accounts_page_renders_for_an_administrator(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Staff accounts');
    }

    public function test_valid_invitation_form_is_publicly_available(): void
    {
        $invitation = $this->createInvitation();

        $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->assertSee($this->department->name)
            ->assertSee('Activate staff account');
    }

    private function createInvitation(array $attributes = []): InvitationCode
    {
        return InvitationCode::create([
            'code' => Str::random(32),
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'expires_at' => now()->addMinutes(10),
            'max_uses' => 1,
            'used_count' => 0,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function redeemInvitation(InvitationCode $invitation, string $email)
    {
        return $this->post(route('invitations.redeem', $invitation->code), [
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);
    }
}
