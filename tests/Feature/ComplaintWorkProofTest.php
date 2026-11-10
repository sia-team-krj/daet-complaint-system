<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\WorkExecutionMode;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ComplaintWorkService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The rule the office asked for: a repair is proven by a photograph of it,
 * and a message is never a substitute for one.
 *
 * The rule is not one-size-fits-all. A pothole is labor work and has to be
 * photographed; a noise complaint is an enforcement action with no site work
 * to photograph, and demanding a photo of it would be theatre. The kind of
 * work comes from the category (config/complaint_work_policy.php), and the
 * same rule applies to an administrator as to a department.
 */
class ComplaintWorkProofTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $staff;

    private User $admin;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
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

    public function test_labor_work_cannot_be_reported_by_message_alone(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The field team has started work on the reported pothole.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $this->assertSame(ComplaintStatus::UnderReview->value, $complaint->fresh()->status);
        $this->assertDatabaseCount('complaint_logs', 0);
    }

    public function test_the_refusal_names_the_work_and_the_status(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The field team has started work on the reported pothole.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $message = session('errors')->first('documentation_images');
        $this->assertStringContainsString('labor work', $message);
        $this->assertStringContainsString('in progress', $message);
    }

    public function test_a_report_only_complaint_is_not_demanded_a_photograph(): void
    {
        // A noise complaint is an enforcement action. There is no site work
        // to photograph, so demanding one would be theatre.
        $complaint = $this->complaint('noise_complaint', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'A liaison visited the premises and issued a warning.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);
    }

    public function test_an_administrator_cannot_settle_labor_work_without_the_photograph(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::InProgress);

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'staff_note' => 'Marked as repaired by the administrator.',
            ])
            ->assertSessionHasErrors('documentation_images');

        $this->assertSame(ComplaintStatus::InProgress->value, $complaint->fresh()->status);
    }

    public function test_an_administrator_can_settle_labor_work_with_the_photograph(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::InProgress);

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'staff_note' => 'The shoulder was patched and the site cleared.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::Resolved->value, $complaint->fresh()->status);

        $log = ComplaintLog::where('complaint_id', $complaint->id)
            ->where('new_status', ComplaintStatus::Resolved->value)
            ->sole();

        $this->assertCount(1, $log->documentation_images);
    }

    public function test_an_administrator_can_settle_a_report_only_complaint_without_a_photograph(): void
    {
        $complaint = $this->complaint('noise_complaint', ComplaintStatus::InProgress);

        $this->actingAs($this->admin)
            ->put(route('admin.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'staff_note' => 'The warning was issued and the matter closed.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint));

        $this->assertSame(ComplaintStatus::Resolved->value, $complaint->fresh()->status);
    }

    public function test_the_work_mode_is_frozen_once_the_ticket_is_actioned(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'The crew arrived and began the repair.',
                'documentation_images' => $this->complaintEvidenceSet(1),
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertSame(
            WorkExecutionMode::LaborRequired->value,
            $complaint->fresh()->work_mode,
        );

        // A later policy change must not move the goalposts on a ticket that
        // is already in flight.
        Config::set('complaint_work_policy.categories.road_damage', 'report_only');

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::Resolved->value,
                'note' => 'The repair was finished and the site cleared.',
            ])
            ->assertSessionHasErrors('documentation_images');
    }

    public function test_the_office_may_override_the_work_mode_for_one_complaint(): void
    {
        // A streetlight that turns out to need a crew is labor work, even
        // though its category is not a repair.
        $complaint = $this->complaint('streetlight', ComplaintStatus::UnderReview);

        $service = app(ComplaintWorkService::class);
        $service->overrideMode($complaint, WorkExecutionMode::LaborRequired, $this->staff, app(ActivityLogger::class));

        $this->assertSame(
            WorkExecutionMode::LaborRequired->value,
            $complaint->fresh()->work_mode,
        );

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.update', $complaint), [
                'status' => ComplaintStatus::InProgress->value,
                'note' => 'A crew was dispatched to replace the pole.',
            ])
            ->assertSessionHasErrors('documentation_images');
    }

    public function test_the_staff_page_states_the_kind_of_work(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Labor work');
    }

    public function test_the_admin_page_offers_the_same_proof_capture(): void
    {
        $complaint = $this->complaint('road_damage', ComplaintStatus::InProgress);

        $this->actingAs($this->admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Photo library')
            ->assertSee('Use camera')
            ->assertSee('proof-capture__previews', false)
            ->assertSee('name="documentation_images[]"', false);
    }

    public function test_a_report_only_complaint_demands_no_photograph_on_the_staff_form(): void
    {
        $complaint = $this->complaint('noise_complaint', ComplaintStatus::UnderReview);

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('Optional for this update')
            ->assertDontSee('proof-capture__note--required', false);
    }

    private function complaint(string $category, ComplaintStatus $status): Complaint
    {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => $category,
            'status' => $status->value,
            'review_status' => 'verified',
            'confirmed_priority' => 'elevated',
        ]);
    }
}
