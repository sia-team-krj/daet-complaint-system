<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);
    }

    public function test_department_creation_is_logged_with_actor_and_department(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), [
            'name' => 'Office of Public Information',
            'code' => 'OPI',
            'description' => 'Public information services.',
        ])->assertRedirect(route('admin.departments.index'));

        $log = ActivityLog::where('action', 'department.created')->sole();

        $this->assertSame($this->admin->id, $log->actor_id);
        $this->assertSame($this->admin->full_name, $log->actor_name);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame('OPI', $log->subject?->code);
        $this->assertNotNull($log->created_at);
    }

    public function test_staff_complaint_actions_create_activity_and_correct_complaint_logs(): void
    {
        Mail::fake();

        $complaint = Complaint::factory()->create([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'department_id' => $this->department->id,
            'status' => ComplaintStatus::Submitted->value,
        ]);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::UnderReview->value,
                'note' => 'The field team has been dispatched to inspect the site.',
                'internal_note' => 'Contractor reference: ENG-2026-014.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.status_updated',
            'actor_id' => $this->staff->id,
            'subject_id' => $complaint->id,
            'subject_type' => $complaint->getMorphClass(),
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'complaint.internal_note_added',
            'actor_id' => $this->staff->id,
            'subject_id' => $complaint->id,
        ]);

        $publicLog = ComplaintLog::where('complaint_id', $complaint->id)->where('is_internal', false)->sole();
        $internalLog = ComplaintLog::where('complaint_id', $complaint->id)->where('is_internal', true)->sole();

        $this->assertSame($this->staff->id, $publicLog->actor_id);
        $this->assertSame($this->staff->id, $internalLog->actor_id);
        $this->assertSame(ComplaintStatus::Submitted->value, $internalLog->new_status);
    }

    public function test_admin_staff_account_changes_are_logged(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.staff.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.com',
            'contact_number' => '09171234567',
            'department_id' => $this->department->id,
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'maria.santos@example.com')->sole();

        $this->actingAs($this->admin)
            ->post(route('admin.staff.toggle', $staff))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff.created',
            'actor_id' => $this->admin->id,
            'subject_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff.deactivated',
            'actor_id' => $this->admin->id,
            'subject_id' => $staff->id,
        ]);
    }

    public function test_admin_audit_page_can_filter_by_department(): void
    {
        $complaint = Complaint::factory()->create([
            'department_id' => $this->department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ]);

        ActivityLog::create([
            'actor_id' => $this->staff->id,
            'actor_name' => $this->staff->full_name,
            'actor_role' => 'staff',
            'department_id' => $this->department->id,
            'action' => 'complaint.reviewed',
            'subject_type' => $complaint->getMorphClass(),
            'subject_id' => $complaint->id,
            'description' => 'Complaint reviewed by the department.',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit.index', ['department_id' => $this->department->id]))
            ->assertOk()
            ->assertSee('Complaint reviewed by the department.')
            ->assertSee($this->department->name);
    }

    public function test_activity_records_cannot_be_updated_or_deleted(): void
    {
        $log = ActivityLog::create([
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->full_name,
            'actor_role' => 'admin',
            'action' => 'test.record',
            'description' => 'Test activity record.',
            'created_at' => now(),
        ]);

        $this->expectException(\LogicException::class);
        $log->update(['description' => 'Tampered description.']);
    }
}
