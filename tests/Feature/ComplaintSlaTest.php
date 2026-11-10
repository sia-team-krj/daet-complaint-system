<?php

namespace Tests\Feature;

use App\Enums\ComplaintSlaState;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Services\ComplaintSlaService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The clock on the work a department owes.
 *
 * A report becomes the office's responsibility the moment it is verified.
 * From then the office owes the resident two things: to start the work, and
 * to finish it. These tests pin that clock — when it starts, when it is late,
 * and who is told.
 */
class ComplaintSlaTest extends TestCase
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

    public function test_nothing_is_owed_before_the_report_is_verified(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview, verifiedAt: null);

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        $this->assertSame(ComplaintSlaState::NotApplicable, $summary['state']);
        $this->assertSame('verify', $summary['action']);
    }

    public function test_verifying_a_report_starts_the_clock(): void
    {
        $complaint = $this->complaint(ComplaintStatus::Submitted, verifiedAt: null);

        $this->assertNull($complaint->verified_at);

        $this->actingAs($this->staff)
            ->put(route('staff.complaints.review', $complaint), [
                'review_status' => 'verified',
                'confirmed_priority' => 'elevated',
                'verification_method' => 'desk',
                'review_notes' => 'The report was checked against the office record.',
            ])
            ->assertRedirect(route('staff.complaints.show', $complaint));

        $this->assertNotNull($complaint->fresh()->verified_at);
    }

    public function test_a_freshly_verified_report_is_on_track(): void
    {
        // Routine work is given three days to start; six hours in leaves two
        // whole days, which is comfortably on track.
        $complaint = $this->complaint(ComplaintStatus::UnderReview, priority: 'routine', verifiedAt: now()->subHours(6));

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        $this->assertSame(ComplaintSlaState::OnTrack, $summary['state']);
        $this->assertSame('start_work', $summary['action']);
        $this->assertGreaterThan(0, $summary['days']);
    }

    public function test_work_that_has_not_started_past_its_deadline_is_overdue(): void
    {
        // Elevated priority is given two days to start.
        $complaint = $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subDays(5));

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        $this->assertSame(ComplaintSlaState::Overdue, $summary['state']);
        $this->assertSame('start_work', $summary['action']);
        $this->assertLessThan(0, $summary['days']);
    }

    public function test_work_that_is_under_way_is_measured_against_the_finishing_deadline(): void
    {
        $complaint = $this->complaint(ComplaintStatus::InProgress, verifiedAt: now()->subDays(5));

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        // Elevated priority is given ten days to finish, so five days in is
        // still on track — it is the start deadline that was missed.
        $this->assertSame(ComplaintSlaState::OnTrack, $summary['state']);
        $this->assertSame('complete_work', $summary['action']);
    }

    public function test_work_that_has_run_past_the_finishing_deadline_is_overdue(): void
    {
        $complaint = $this->complaint(ComplaintStatus::InProgress, verifiedAt: now()->subDays(20));

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        $this->assertSame(ComplaintSlaState::Overdue, $summary['state']);
        $this->assertSame('complete_work', $summary['action']);
    }

    public function test_a_critical_report_is_given_less_time(): void
    {
        // A critical report is given one day to start; a routine one three.
        // Two days in, the critical report is already late and the routine
        // one is not.
        $critical = $this->complaint(ComplaintStatus::UnderReview, priority: 'critical', verifiedAt: now()->subDays(2));
        $routine = $this->complaint(ComplaintStatus::UnderReview, priority: 'routine', verifiedAt: now()->subDays(2));

        $sla = app(ComplaintSlaService::class);

        $this->assertSame(ComplaintSlaState::Overdue, $sla->summary($critical)['state']);
        $this->assertNotSame(ComplaintSlaState::Overdue, $sla->summary($routine)['state']);
        $this->assertSame(1, $sla->startDays('critical'));
        $this->assertSame(3, $sla->startDays('routine'));
    }

    public function test_a_finished_report_owes_nothing_more(): void
    {
        $complaint = $this->complaint(ComplaintStatus::Resolved, verifiedAt: now()->subDays(30));

        $summary = app(ComplaintSlaService::class)->summary($complaint);

        $this->assertSame(ComplaintSlaState::Met, $summary['state']);
        $this->assertNull($summary['action']);
    }

    public function test_the_queue_shows_the_time_left_and_can_filter_to_the_overdue(): void
    {
        $late = $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subDays(6));
        $fresh = $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subHours(4));

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.index'))
            ->assertOk()
            ->assertSee('sla-chip', false)
            ->assertSee(__('staff.sla.action_start'))
            ->assertSee(trans_choice('staff.sla.days_overdue', 4));

        $this->actingAs($this->staff)
            ->get(route('staff.complaints.index', ['overdue' => '1']))
            ->assertOk()
            ->assertSee($late->ticket_id)
            ->assertDontSee($fresh->ticket_id);
    }

    public function test_the_admin_dashboard_counts_the_work_that_has_slipped(): void
    {
        $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subDays(9));

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('dept-department-card__overdue', false)
            ->assertSee(trans_choice('staff.sla.overdue_count', 1));
    }

    public function test_the_sweep_tells_the_office_and_the_administrators(): void
    {
        $complaint = $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subDays(9));

        $this->artisan('complaints:flag-overdue-work')
            ->assertExitCode(0);

        // One to the office that owes the work, one to the administrator who
        // can see the whole municipality.
        $this->assertSame(2, Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)->count());
        $this->assertTrue(
            Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)
                ->where('user_id', $this->staff->id)
                ->exists(),
            'The office that owes the work must be told.',
        );
        $this->assertTrue(
            Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)
                ->where('user_id', $this->admin->id)
                ->exists(),
            'An administrator must be able to see that a report has slipped.',
        );
        $this->assertSame(
            $complaint->id,
            Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)->first()->subject_id,
        );
    }

    public function test_the_sweep_does_not_ask_the_same_office_twice(): void
    {
        $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subDays(9));

        $this->artisan('complaints:flag-overdue-work')->assertExitCode(0);
        $this->artisan('complaints:flag-overdue-work')->assertExitCode(0);

        $this->assertSame(2, Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)->count());
    }

    public function test_the_sweep_leaves_work_that_is_inside_its_deadline_alone(): void
    {
        $this->complaint(ComplaintStatus::UnderReview, verifiedAt: now()->subHours(3));

        $this->artisan('complaints:flag-overdue-work')->assertExitCode(0);

        $this->assertSame(0, Notification::where('type', Notification::COMPLAINT_WORK_OVERDUE)->count());
    }

    private function complaint(
        ComplaintStatus $status,
        ?string $priority = 'elevated',
        ?Carbon $verifiedAt = null,
    ): Complaint {
        return Complaint::factory()->create([
            'user_id' => $this->resident->id,
            'department_id' => $this->department->id,
            'category' => 'road_damage',
            'status' => $status->value,
            'review_status' => $verifiedAt !== null ? 'verified' : 'pending',
            'confirmed_priority' => $priority,
            'verified_at' => $verifiedAt,
            'reviewed_at' => $verifiedAt,
        ]);
    }
}
