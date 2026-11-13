<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentAnalyticsService;
use App\Support\AnalyticsPeriod;
use Carbon\CarbonImmutable;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DepartmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Department $engineering;

    private Department $menro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
        $this->engineering = Department::where('code', 'ENG')->firstOrFail();
        $this->menro = Department::where('code', 'MENRO')->firstOrFail();
    }

    public function test_staff_page_renders_only_their_department_and_no_staff_breakdown(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $otherStaff = $this->staff($this->engineering, 'Alex', 'Worker');

        $own = $this->complaint($this->engineering, [
            'title' => 'Broken streetlight on Main Street',
            'assigned_staff_id' => $otherStaff->id,
        ]);
        $other = $this->complaint($this->menro, [
            'title' => 'Uncollected garbage in Barangay One',
        ]);

        ComplaintLog::create([
            'complaint_id' => $own->id,
            'actor_id' => $staff->id,
            'previous_status' => ComplaintStatus::Submitted->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'is_internal' => false,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.analytics.index'))
            ->assertOk()
            ->assertSee(__('analytics.title'))
            ->assertSee($this->engineering->name)
            ->assertSee($own->ticket_id)
            ->assertSee('data-an-chart-band', false)
            ->assertSee('data-an-donut-hit', false)
            ->assertSee('data-an-bar-hit', false)
            ->assertDontSee($other->ticket_id)
            ->assertDontSee($this->menro->name)
            ->assertDontSee('Alex Worker')
            ->assertSee(__('analytics.sections.workload_staff_note'));
    }

    public function test_admin_comparison_is_alphabetical_and_excludes_archived_offices(): void
    {
        $this->skipIfAnalyticsPaused();

        $admin = $this->admin();

        $archived = Department::create([
            'name' => 'Archived Office',
            'code' => 'ARC',
            'is_active' => false,
        ]);

        $this->complaint($this->engineering);
        $this->complaint($archived);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Business and Licensing Office',
                'Engineering Office',
                'Human Resource Management Office',
                "Mayor's Office",
                'Municipal Environment and Natural Resource Office',
            ])
            ->assertDontSee('Archived Office')
            ->assertSee(__('analytics.comparison.fairness_title'))
            ->assertSee(__('analytics.comparison.fairness_body'));
    }

    public function test_admin_department_page_includes_the_administrator_only_staff_breakdown(): void
    {
        $this->skipIfAnalyticsPaused();

        $admin = $this->admin();
        $worker = $this->staff($this->engineering, 'Alex', 'Worker');

        $this->complaint($this->engineering, [
            'assigned_staff_id' => $worker->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.department', $this->engineering))
            ->assertOk()
            ->assertSee($this->engineering->name)
            ->assertSee(__('analytics.sections.staff_breakdown_sub'))
            ->assertSee('Alex Worker')
            ->assertSee(__('analytics.back_to_comparison'));
    }

    public function test_admin_department_without_staff_shows_an_honest_empty_table(): void
    {
        $this->skipIfAnalyticsPaused();

        $admin = $this->admin();
        $emptyOffice = Department::where('code', 'OM')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.analytics.department', $emptyOffice))
            ->assertOk()
            ->assertSee(__('analytics.sections.no_staff'))
            ->assertDontSee(__('analytics.sections.workload_staff_note'));
    }

    public function test_admin_can_open_an_archived_department_report(): void
    {
        $this->skipIfAnalyticsPaused();

        $admin = $this->admin();
        $archived = Department::create([
            'name' => 'Archived Office',
            'code' => 'ARC',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.department', $archived))
            ->assertOk()
            ->assertSee('Archived Office')
            ->assertSee(__('analytics.comparison.archived'));
    }

    public function test_staff_cannot_open_the_admin_analytics_surface(): void
    {        $staff = $this->staff($this->engineering);

        $this->actingAs($staff)
            ->get(route('admin.analytics.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($staff)
            ->get(route('admin.analytics.department', $this->engineering))
            ->assertRedirect(route('dashboard'));
    }

    public function test_an_administrator_without_a_department_cannot_open_the_staff_surface(): void
    {
        $this->actingAs($this->admin())
            ->get(route('staff.analytics.index'))
            ->assertForbidden();
    }

    public function test_resolved_and_closed_count_as_completed_but_rejected_never_does(): void
    {
        $this->complaint($this->engineering, [
            'status' => ComplaintStatus::Resolved->value,
            'resolved_at' => now(),
        ]);
        $this->complaint($this->engineering, [
            'status' => ComplaintStatus::Closed->value,
            'closed_at' => now(),
        ]);
        $this->complaint($this->engineering, [
            'status' => ComplaintStatus::Rejected->value,
        ]);
        $this->complaint($this->engineering, [
            'status' => ComplaintStatus::InProgress->value,
        ]);

        $metrics = app(DepartmentAnalyticsService::class)
            ->metrics($this->engineering, AnalyticsPeriod::preset('month'));

        $this->assertSame(2, $metrics['resolved']);
        $this->assertSame(4, $metrics['filed']);
        $this->assertSame(1, $metrics['open']);
    }

    public function test_completion_uses_resolved_at_then_closed_at_then_updated_at(): void
    {
        $this->skipIfAnalyticsPaused();

        $resolved = $this->complaint($this->engineering, [
            'status' => ComplaintStatus::Resolved->value,
            'created_at' => now()->subDays(7),
            'resolved_at' => now()->subDays(2),
            'closed_at' => now()->subDays(5),
            'updated_at' => now()->subDays(6),
        ]);
        $closed = $this->complaint($this->engineering, [
            'status' => ComplaintStatus::Closed->value,
            'created_at' => now()->subDays(5),
            'resolved_at' => null,
            'closed_at' => now()->subDays(1),
            'updated_at' => now()->subDays(8),
        ]);

        $metrics = app(DepartmentAnalyticsService::class)
            ->metrics($this->engineering, AnalyticsPeriod::preset('month'));

        $this->assertSame(2, $metrics['resolved']);
        $this->assertSame(4.5, $metrics['avg_days']);

        $dates = $this->actingAs($this->staff($this->engineering))
            ->get(route('staff.analytics.export', ['period' => 'month']))
            ->streamedContent();

        $this->assertStringContainsString($resolved->ticket_id, $dates);
        $this->assertStringContainsString($closed->ticket_id, $dates);
        $this->assertStringContainsString(now()->subDays(2)->format('Y-m-d'), $dates);
        $this->assertStringContainsString(now()->subDays(1)->format('Y-m-d'), $dates);
    }

    public function test_on_time_rate_counts_only_verified_completions_and_uses_the_priority_deadline(): void
    {
        $complaint = fn (array $attributes): Complaint => $this->complaint($this->engineering, array_merge([
            'status' => ComplaintStatus::Resolved->value,
            'resolved_at' => now(),
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'created_at' => now()->subDays(40),
        ], $attributes));

        $complaint(['verified_at' => now()->subDays(5)]);   // within 14 days
        $complaint(['verified_at' => now()->subDays(20)]);  // past 14 days
        $complaint(['verified_at' => null]);                // not part of the base

        $metrics = app(DepartmentAnalyticsService::class)
            ->metrics($this->engineering, AnalyticsPeriod::preset('month'));

        $this->assertSame(1, $metrics['on_time']);
        $this->assertSame(2, $metrics['on_time_base']);
        $this->assertSame(50.0, $metrics['on_time_rate']);
    }

    public function test_custom_period_reads_its_dates_from_the_query_string_and_falls_back_when_invalid(): void
    {
        $custom = AnalyticsPeriod::fromRequest(new Request([
            'period' => 'custom',
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));

        $this->assertSame('custom', $custom->preset);
        $this->assertSame('2026-01-01', $custom->start->toDateString());
        $this->assertSame('2026-01-31', $custom->end->toDateString());

        // The previous window has the same length.
        $this->assertSame('2025-12-01', $custom->previous()->start->toDateString());
        $this->assertSame('2025-12-31', $custom->previous()->end->toDateString());

        $invalid = AnalyticsPeriod::fromRequest(new Request([
            'period' => 'custom',
            'from' => '2026-02-31',
            'to' => '2026-03-01',
        ]));

        $this->assertSame(AnalyticsPeriod::DEFAULT, $invalid->preset);

        $backwards = AnalyticsPeriod::fromRequest(new Request([
            'period' => 'custom',
            'from' => '2026-03-10',
            'to' => '2026-03-01',
        ]));

        $this->assertSame(AnalyticsPeriod::DEFAULT, $backwards->preset);
    }

    public function test_fragment_endpoint_answers_json_and_redirects_plain_requests_to_the_page(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);

        $this->actingAs($staff)
            ->getJson(route('staff.analytics.browser', ['period' => '7d']))
            ->assertOk()
            ->assertJsonStructure(['period', 'content'])
            ->assertJsonPath('period', fn ($value) => is_string($value) && str_contains($value, 'an-period'));

        $this->actingAs($staff)
            ->get(route('staff.analytics.browser', ['period' => '7d']))
            ->assertRedirect(route('staff.analytics.index', ['period' => '7d']));

        $this->actingAs($this->admin())
            ->get(route('admin.analytics.department.browser', $this->engineering))
            ->assertRedirect(route('admin.analytics.department', [
                'department' => $this->engineering->id,
            ]));
    }

    public function test_csv_export_contains_the_complaint_register_without_resident_identity(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $resident = User::factory()->create([
            'role' => 'citizen',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.test',
        ]);
        $complaint = $this->complaint($this->engineering, [
            'user_id' => $resident->id,
            'title' => 'Pothole on Main Street',
            'barangay' => 'Lag-on',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('staff.analytics.export', ['period' => 'month']))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString(__('analytics.csv.ticket'), $csv);
        $this->assertStringContainsString($complaint->ticket_id, $csv);
        $this->assertStringContainsString('Pothole on Main Street', $csv);
        $this->assertStringContainsString('Lag-on', $csv);
        $this->assertStringNotContainsString('Maria', $csv);
        $this->assertStringNotContainsString('Santos', $csv);
        $this->assertStringNotContainsString('maria.santos@example.test', $csv);
    }

    public function test_print_views_render_for_staff_and_admin(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $admin = $this->admin();
        $worker = $this->staff($this->engineering, 'Alex', 'Worker');

        $this->complaint($this->engineering, [
            'assigned_staff_id' => $worker->id,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.analytics.print', ['period' => '90d']))
            ->assertOk()
            ->assertSee(__('analytics.print.confidential'))
            ->assertSee($this->engineering->name)
            ->assertDontSee('Alex Worker');

        $this->actingAs($admin)
            ->get(route('admin.analytics.print', ['period' => '30d']))
            ->assertOk()
            ->assertSee(__('analytics.comparison_title'))
            ->assertSee(__('analytics.comparison.fairness_title'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.department.print', $this->engineering))
            ->assertOk()
            ->assertSee(__('analytics.print.confidential'))
            ->assertSee('Alex Worker');
    }

    public function test_recent_activity_is_scoped_to_the_department_and_period(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $own = $this->complaint($this->engineering, ['title' => 'Own department report']);
        $other = $this->complaint($this->menro, ['title' => 'Other department report']);

        ComplaintLog::create([
            'complaint_id' => $own->id,
            'actor_id' => $staff->id,
            'previous_status' => ComplaintStatus::Submitted->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'Crew dispatched',
            'is_internal' => false,
        ]);

        ComplaintLog::create([
            'complaint_id' => $other->id,
            'actor_id' => $staff->id,
            'previous_status' => ComplaintStatus::Submitted->value,
            'new_status' => ComplaintStatus::InProgress->value,
            'comment' => 'Should not appear',
            'is_internal' => false,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.analytics.index'))
            ->assertOk()
            ->assertSee('Crew dispatched')
            ->assertDontSee('Should not appear')
            ->assertSee('Own department report');
    }

    public function test_design_system_swatch_renders_the_analytics_components(): void
    {
        $this->actingAs($this->admin())
            ->get(route('design-system'))
            ->assertOk()
            ->assertSee('Department analytics — §5.43')
            ->assertSee(__('analytics.sections.trend'))
            ->assertSee(__('analytics.sections.status_mix'))
            ->assertSee(__('analytics.comparison.fairness_title'));
    }

    public function test_delta_renders_nothing_when_the_current_value_is_missing(): void
    {
        $html = view('analytics.partials.delta', [
            'current' => null,
            'previous' => 4.2,
            'type' => 'days',
        ])->render();

        $this->assertStringNotContainsString('an-delta', $html);
        $this->assertStringNotContainsString('4.2', $html);
    }

    public function test_recent_activity_labels_the_first_log_as_filed(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $complaint = $this->complaint($this->engineering, ['title' => 'Fresh pothole report']);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $complaint->user_id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::Submitted->value,
            'comment' => 'Complaint filed and routed',
            'is_internal' => false,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.analytics.index'))
            ->assertOk()
            ->assertSee('filed')
            ->assertSee($complaint->ticket_id);
    }

    public function test_recent_activity_shows_the_latest_eight_and_discloses_the_rest(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);
        $complaint = $this->complaint($this->engineering, ['title' => 'Activity pagination report']);

        foreach (range(1, 10) as $index) {
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'actor_id' => $staff->id,
                'previous_status' => ComplaintStatus::Submitted->value,
                'new_status' => ComplaintStatus::InProgress->value,
                'comment' => 'Activity entry '.$index,
                'is_internal' => false,
            ]);
        }

        $response = $this->actingAs($staff)
            ->get(route('staff.analytics.index'))
            ->assertOk()
            ->assertSee('an-feed__more', false)
            ->assertSee(trans_choice('analytics.activity.show_more', 2, ['count' => 2]));

        $this->assertSame(10, substr_count($response->getContent(), 'an-feed__item'));
    }

    public function test_staff_sidebar_links_to_their_analytics_page(): void
    {
        $this->skipIfAnalyticsPaused();

        $staff = $this->staff($this->engineering);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee(route('staff.analytics.index'), false)
            ->assertSee(__('nav.analytics'));
    }

    public function test_trend_buckets_follow_the_period_length(): void
    {
        $week = AnalyticsPeriod::preset('7d');
        $this->assertSame('daily', $week->granularity());
        $this->assertCount(7, $week->buckets());

        $quarter = AnalyticsPeriod::preset('90d');
        $this->assertSame('weekly', $quarter->granularity());
        $this->assertCount(13, $quarter->buckets());

        $year = AnalyticsPeriod::preset('ytd', CarbonImmutable::parse('2026-11-12'));
        $this->assertSame('monthly', $year->granularity());
        $this->assertCount(11, $year->buckets());
    }

    private function staff(Department $department, string $firstName = 'Sam', string $lastName = 'Staffer'): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'department_id' => $department->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function complaint(Department $department, array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'department_id' => $department->id,
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
        ], $attributes));
    }

    /**
     * The analytics is parked: the pages are out of sight until the
     * municipality is ready for them. These tests come back with the feature.
     */
    private function skipIfAnalyticsPaused(): void
    {
        if (\App\Http\Middleware\AnalyticsPaused::PAUSED) {
            $this->markTestSkipped('The department analytics is parked.');
        }
    }
}
