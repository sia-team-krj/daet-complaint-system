<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Closure belongs to the resident, or to the system.
 *
 * The office finishes the work and marks the report resolved; the resident
 * confirms it is done for them (or the scheduled command closes it seven
 * days later). Neither the department nor an administrator may close a
 * resolved report out from under the resident.
 */
class ResidentOnlyClosureTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

    private User $admin;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
            'can_verify' => true,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        $this->resident = User::factory()->create(['role' => 'citizen']);
    }

    public function test_the_resident_can_close_a_resolved_report(): void
    {
        $complaint = $this->resolvedComplaint();

        $this->actingAs($this->resident)
            ->post(route('complaints.close', $complaint))
            ->assertRedirect(route('complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::Closed->value, $complaint->fresh()->status);
        $this->assertSame('resident', $complaint->fresh()->closed_by);
    }

    public function test_the_staff_status_form_does_not_offer_closed_nor_the_form_for_a_resolved_report(): void
    {
        $complaint = $this->resolvedComplaint();

        $response = $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk();

        $response->assertSee(__('staff.closure.waiting_title'));
        $response->assertDontSee('name="status"', false);
        $response->assertDontSee('value="Closed"', false);
    }

    public function test_a_staff_member_cannot_close_a_resolved_report(): void
    {
        $complaint = $this->resolvedComplaint();

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::Closed->value,
                'note' => 'Everything is done and the report can be closed.',
            ])
            ->assertSessionHas('error', __('staff.closure.resident_only'));

        $fresh = $complaint->fresh();
        $this->assertSame(ComplaintStatus::Resolved->value, $fresh->status);
        $this->assertNull($fresh->closed_at);
        $this->assertNull($fresh->closed_by);
        $this->assertSame(
            0,
            ComplaintLog::where('complaint_id', $complaint->id)
                ->where('new_status', ComplaintStatus::Closed->value)
                ->count(),
        );
    }

    public function test_the_administrator_status_form_does_not_offer_closed_for_a_resolved_report(): void
    {
        $complaint = $this->resolvedComplaint();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk();

        $response->assertDontSee('value="Closed"', false);
    }

    public function test_an_administrator_cannot_close_a_resolved_report(): void
    {
        $complaint = $this->resolvedComplaint();

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Closed->value,
                'staff_note' => 'Closing this out on the resident’s behalf.',
            ])
            ->assertSessionHas('error', __('staff.closure.resident_only'));

        $fresh = $complaint->fresh();
        $this->assertSame(ComplaintStatus::Resolved->value, $fresh->status);
        $this->assertNull($fresh->closed_at);
        $this->assertNull($fresh->closed_by);
    }

    public function test_staff_can_still_move_the_work_forward(): void
    {
        $complaint = Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'noise_complaint',
            'status' => ComplaintStatus::InProgress->value,
            'review_status' => 'verified',
        ]);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'note' => 'The warning was issued and the matter is settled.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::Resolved->value, $complaint->fresh()->status);
    }

    public function test_the_system_closes_a_resolved_report_after_seven_days(): void
    {
        $complaint = $this->resolvedComplaint([
            'resolved_at' => now()->subDays(8),
        ]);

        $this->artisan('complaints:auto-close-resolved')->assertSuccessful();

        $fresh = $complaint->fresh();
        $this->assertSame(ComplaintStatus::Closed->value, $fresh->status);
        $this->assertSame('system', $fresh->closed_by);
        $this->assertNotNull($fresh->closed_at);
    }

    private function resolvedComplaint(array $overrides = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'noise_complaint',
            'status' => ComplaintStatus::Resolved->value,
            'review_status' => 'verified',
            'resolved_at' => now(),
        ], $overrides));
    }
}
