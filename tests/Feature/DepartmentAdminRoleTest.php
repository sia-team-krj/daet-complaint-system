<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Department Administrator is the office's manager, not a second global
 * administrator. These tests hold the boundary: one office, staff management
 * only, no peer control, no resident identity, and a trail that never lists
 * resident actions.
 */
class DepartmentAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Department $otherDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->otherDepartment = Department::where('code', 'MENRO')->firstOrFail();
    }

    public function test_a_department_admin_lands_in_the_staff_workspace_and_can_manage_their_office(): void
    {
        $admin = $this->departmentAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($admin)
            ->get(route('staff.office.index'))
            ->assertOk()
            ->assertSee('Department Administrator')
            ->assertSee($this->department->name)
            ->assertSee('Manage office');
    }

    public function test_a_department_admin_verifies_without_the_can_verify_flag(): void
    {
        $admin = $this->departmentAdmin();
        $complaint = $this->complaint();

        $this->assertFalse((bool) $admin->can_verify);

        $this->actingAs($admin)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertRedirect();

        $this->assertSame('verified', $complaint->fresh()->review_status);
    }

    public function test_a_department_admin_cannot_open_admin_routes(): void
    {
        $this->actingAs($this->departmentAdmin())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_a_department_admin_cannot_see_or_verify_another_office(): void
    {
        $admin = $this->departmentAdmin();
        $complaint = $this->complaint($this->otherDepartment);

        $this->actingAs($admin)
            ->get(route('staff.complaints.show', $complaint))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'routine',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $complaint->fresh()->review_status);
    }

    public function test_assignment_stays_inside_the_office(): void
    {
        $admin = $this->departmentAdmin();
        $ownStaff = $this->staff();
        $outsider = $this->staff($this->otherDepartment);
        $complaint = $this->complaint();

        $this->actingAs($admin)
            ->post(route('staff.office.complaints.assign', $complaint), [
                'assigned_staff_id' => $ownStaff->id,
            ])
            ->assertRedirect();

        $this->assertSame($ownStaff->id, $complaint->fresh()->assigned_staff_id);

        $this->actingAs($admin)
            ->post(route('staff.office.complaints.assign', $complaint), [
                'assigned_staff_id' => $outsider->id,
            ])
            ->assertSessionHasErrors('assigned_staff_id');

        $this->assertSame(
            $ownStaff->id,
            $complaint->fresh()->assigned_staff_id,
            'A refused cross-office assignment must not overwrite the current assignee.',
        );
    }

    public function test_a_department_admin_can_manage_ordinary_staff_but_not_peers_or_themselves(): void
    {
        $admin = $this->departmentAdmin();
        $staff = $this->staff();
        $peer = $this->departmentAdmin();

        $this->actingAs($admin)
            ->post(route('staff.office.staff.toggle', $staff))
            ->assertRedirect();

        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('staff.office.staff.toggle', $peer))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('staff.office.staff.toggle', $admin))
            ->assertForbidden();

        $this->assertTrue($peer->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_verification_rights_can_only_be_granted_to_own_office_staff(): void
    {
        $admin = $this->departmentAdmin();
        $staff = $this->staff();
        $outsider = $this->staff($this->otherDepartment);

        $this->actingAs($admin)
            ->post(route('staff.office.staff.toggle-verification', $staff))
            ->assertRedirect();

        $this->assertTrue($staff->fresh()->can_verify);

        $this->actingAs($admin)
            ->post(route('staff.office.staff.toggle-verification', $outsider))
            ->assertForbidden();

        $this->assertFalse($outsider->fresh()->can_verify);
    }

    public function test_the_office_audit_trail_hides_resident_actions(): void
    {
        $admin = $this->departmentAdmin();
        $staff = $this->staff();

        ActivityLog::create([
            'actor_id' => null,
            'actor_name' => 'Anonymous Resident',
            'actor_role' => 'citizen',
            'department_id' => $this->department->id,
            'action' => 'complaint.created',
            'description' => 'Anonymous Resident filed a complaint.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => $staff->id,
            'actor_name' => $staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'staff.created',
            'description' => 'Staff account created by the office.',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_id' => null,
            'actor_name' => null,
            'actor_role' => null,
            'department_id' => $this->department->id,
            'action' => 'complaint.sla_breached',
            'description' => 'Automated SLA record for the office.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('staff.office.audit.index'))
            ->assertOk()
            ->assertSee('Staff account created by the office.')
            ->assertSee('Automated SLA record for the office.')
            ->assertDontSee('Anonymous Resident')
            ->assertDontSee('filed a complaint');
    }

    public function test_the_global_admin_appoints_and_revokes_the_role(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $staff = $this->staff();

        $this->actingAs($globalAdmin)
            ->patch(route('admin.users.update', $staff), [
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'email' => $staff->email,
                'role' => 'department_admin',
                'department_id' => $this->department->id,
            ])
            ->assertRedirect(route('admin.users.show', $staff));

        $this->assertSame('department_admin', $staff->fresh()->role);

        $this->actingAs($globalAdmin)
            ->get(route('admin.users.show', $staff))
            ->assertOk()
            ->assertSee('Department Administrator');

        $this->actingAs($globalAdmin)
            ->patch(route('admin.users.update', $staff), [
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'email' => $staff->email,
                'role' => 'staff',
                'department_id' => $this->department->id,
            ])
            ->assertRedirect(route('admin.users.show', $staff));

        $this->assertSame('staff', $staff->fresh()->role);
    }

    public function test_the_role_requires_an_active_department(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $resident = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($globalAdmin)
            ->patch(route('admin.users.update', $resident), [
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'role' => 'department_admin',
                'department_id' => null,
            ])
            ->assertSessionHasErrors('department_id');

        $this->otherDepartment->update(['is_active' => false]);

        $this->actingAs($globalAdmin)
            ->patch(route('admin.users.update', $resident), [
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'role' => 'department_admin',
                'department_id' => $this->otherDepartment->id,
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertSame('citizen', $resident->fresh()->role);
    }

    public function test_onboarding_offers_only_an_invitation_or_an_emailed_temporary_password(): void
    {
        Mail::fake();

        $admin = $this->departmentAdmin();

        $this->actingAs($admin)
            ->post(route('staff.office.staff.store'), [
                'first_name' => 'Invited',
                'last_name' => 'Operator',
                'email' => 'invited.operator@example.com',
                'creation_method' => 'invitation',
                'invitation_expires_in_hours' => 24,
            ])
            ->assertRedirect(route('staff.office.index'));

        $this->assertDatabaseHas('invitation_codes', [
            'invitee_email' => 'invited.operator@example.com',
            'department_id' => $this->department->id,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'invited.operator@example.com']);

        $this->actingAs($admin)
            ->post(route('staff.office.staff.store'), [
                'first_name' => 'Emailed',
                'last_name' => 'Operator',
                'email' => 'emailed.operator@example.com',
                'creation_method' => 'emailed_temp_password',
            ])
            ->assertRedirect(route('staff.office.index'));

        $created = User::where('email', 'emailed.operator@example.com')->firstOrFail();

        $this->assertSame('staff', $created->role);
        $this->assertSame($this->department->id, $created->department_id);
        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->password_expires_at);

        $this->actingAs($admin)
            ->post(route('staff.office.staff.store'), [
                'first_name' => 'Manual',
                'last_name' => 'Password',
                'email' => 'manual.password@example.com',
                'creation_method' => 'manual_password',
                'temporary_password' => 'Chosen-By-Manager-1',
                'temporary_password_confirmation' => 'Chosen-By-Manager-1',
            ])
            ->assertSessionHasErrors('creation_method');

        $this->assertDatabaseMissing('users', ['email' => 'manual.password@example.com']);
    }

    public function test_an_office_with_a_department_admin_cannot_be_archived(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $office = Department::create([
            'name' => 'Liaison Office',
            'code' => 'LTO',
            'description' => 'An office created for this test.',
            'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'role' => 'department_admin',
            'department_id' => $office->id,
        ]);

        $this->actingAs($globalAdmin)
            ->from(route('admin.departments.index'))
            ->post(route('admin.departments.archive', $office))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('error');

        $this->assertTrue($office->fresh()->is_active);

        $manager->update(['role' => 'staff']);

        $this->actingAs($globalAdmin)
            ->post(route('admin.departments.archive', $office))
            ->assertRedirect();

        $this->assertFalse($office->fresh()->is_active);
    }

    public function test_resident_identity_stays_with_the_global_admin(): void
    {
        $admin = $this->departmentAdmin();
        $globalAdmin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $complaint = $this->complaint();

        $this->assertFalse(Gate::forUser($admin)->allows('viewRealIdentity', $complaint));
        $this->assertTrue(Gate::forUser($globalAdmin)->allows('viewRealIdentity', $complaint));
    }

    private function departmentAdmin(?Department $department = null): User
    {
        return User::factory()->create([
            'role' => 'department_admin',
            'department_id' => ($department ?? $this->department)->id,
            'can_verify' => false,
        ]);
    }

    private function staff(?Department $department = null): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => ($department ?? $this->department)->id,
            'can_verify' => false,
        ]);
    }

    private function complaint(?Department $department = null): Complaint
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'display_alias' => 'Citizen-Test-'.Str::upper(Str::random(6)),
            'prefers_anonymity' => true,
        ]);

        return Complaint::create([
            'ticket_id' => 'COMP-2026-'.str_pad((string) (Complaint::count() + 1), 5, '0', STR_PAD_LEFT),
            'user_id' => $citizen->id,
            'department_id' => ($department ?? $this->department)->id,
            'category' => 'Road Damage',
            'title' => 'Fallen tree blocking the road',
            'description' => 'A large tree has fallen across the road near the barangay hall.',
            'address_text' => 'Near the barangay hall',
            'status' => 'Submitted',
            'review_status' => 'pending',
            'is_public' => false,
            'content_fingerprint' => Str::random(40),
        ]);
    }
}
