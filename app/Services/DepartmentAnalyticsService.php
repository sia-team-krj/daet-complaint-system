<?php

namespace App\Services;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Support\AnalyticsPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The numbers behind a department's analytics page.
 *
 * The counting rules are stated once here so the page, the fragment endpoint,
 * the CSV register and the printable report cannot drift apart:
 *
 *  - A report belongs to the period it was *filed* in (`created_at`).
 *  - It is *resolved* when its status is Resolved or Closed, and it belongs to
 *    the period it was *completed* in (`resolved_at`, else `closed_at`, else
 *    `updated_at`). Rejected reports never count as resolved.
 *  - The open backlog is what is still Submitted, Under Review or In Progress
 *    right now — a current fact, not a period count.
 *  - On-time means the completion happened within the SLA resolve deadline
 *    that applied to that report's priority, counted only for reports that
 *    were actually verified.
 *
 * There is deliberately no composite score and no ordering by performance.
 * Staffing, equipment, budget and the work each office receives are not equal,
 * so the comparison page lists offices alphabetically and compares each one
 * with its own previous period.
 */
class DepartmentAnalyticsService
{
    /** Work that is still owed to a resident right now. */
    public const OPEN_STATUSES = [
        ComplaintStatus::Submitted->value,
        ComplaintStatus::UnderReview->value,
        ComplaintStatus::InProgress->value,
    ];

    /** A finished report. Rejected is deliberately absent. */
    public const COMPLETED_STATUSES = [
        ComplaintStatus::Resolved->value,
        ComplaintStatus::Closed->value,
    ];

    private const COMPLETION_SQL = 'COALESCE(resolved_at, closed_at, updated_at)';

    public function __construct(private ComplaintSlaService $sla) {}

    /**
     * The department page's complete dataset.
     *
     * `$withStaff` is the administrator-only switch. When false the per-staff
     * rows are never queried, so a staff response cannot contain them by
     * accident — hiding them in Blade would still ship the names.
     *
     * @return array<string, mixed>
     */
    public function department(Department $department, AnalyticsPeriod $period, bool $withStaff = false): array
    {
        $metrics = $this->metrics($department, $period);
        $filedQuery = $this->filedQuery($department, $period);

        $statusMix = $this->statusMix($filedQuery);
        $quality = $this->quality($department, $period, $metrics['filed']);
        $categories = $this->categories($department, $period, $metrics['filed']);
        $barangays = $this->barangays($department, $period, $metrics['filed']);

        return [
            'department' => $department,
            'period' => $period,
            'previous_period' => $period->previous(),
            'metrics' => $metrics,
            'trend' => $this->trend($department, $period),
            'status_mix' => $statusMix,
            'quality' => $quality,
            'categories' => $categories,
            'barangays' => $barangays,
            'aging' => $this->aging($department),
            'workload' => [
                'team' => $metrics['staff_count'],
                'assigned' => $metrics['assigned_open'],
                'unassigned' => $metrics['unassigned_open'],
                'per_staff' => $metrics['per_staff'],
                'staff' => $withStaff ? $this->staffWorkload($department, $period) : [],
            ],
            'activity' => $this->activity($department, $period),
        ];
    }

    /**
     * The municipal comparison: active offices, alphabetical, no ranking.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, mixed>, period: AnalyticsPeriod, previous_period: AnalyticsPeriod}
     */
    public function comparison(AnalyticsPeriod $period): array
    {
        $rows = Department::active()
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'department' => $department,
                'metrics' => $this->metrics($department, $period),
            ]);

        $totals = $this->totals($rows);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'period' => $period,
            'previous_period' => $period->previous(),
        ];
    }

    /**
     * One row per report filed in the period.
     *
     * The register is internal, but it still carries no resident identity:
     * ticket, title, category, barangay, status, verification, priority,
     * office, assignee and the two dates that describe the work. A staff
     * member may already see the report itself; the export must not become a
     * second, unguarded copy of the resident's contact details.
     */
    public function export(Department $department, AnalyticsPeriod $period): StreamedResponse
    {
        $complaints = $this->filedQuery($department, $period)
            ->with([
                'assignedStaff:id,first_name,last_name',
                'department:id,name',
            ])
            ->orderBy('created_at')
            ->get();

        $filename = sprintf(
            'complaint-register-%s-%s-to-%s.csv',
            Str::lower($department->code ?: 'department'),
            $period->start->toDateString(),
            $period->end->toDateString(),
        );

        return response()->streamDownload(function () use ($complaints): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            // Excel on Windows reads a BOM-less UTF-8 CSV as the local code
            // page; the BOM is what keeps a Filipino barangay name intact.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                __('analytics.csv.ticket'),
                __('analytics.csv.title'),
                __('analytics.csv.category'),
                __('analytics.csv.barangay'),
                __('analytics.csv.status'),
                __('analytics.csv.review_status'),
                __('analytics.csv.priority'),
                __('analytics.csv.department'),
                __('analytics.csv.assigned'),
                __('analytics.csv.filed'),
                __('analytics.csv.completed'),
                __('analytics.csv.days'),
            ]);

            foreach ($complaints as $complaint) {
                $completion = $this->completionOf($complaint);

                fputcsv($out, [
                    $complaint->ticket_id,
                    $complaint->title,
                    self::categoryLabel($complaint->category),
                    $complaint->barangay ?: '',
                    $complaint->statusEnum->label(),
                    $complaint->reviewStatusEnum->label(),
                    ($complaint->confirmedPriorityEnum ?? $complaint->suggestedPriorityEnum)->label(),
                    $complaint->department?->name ?? '',
                    $complaint->assignedStaff?->full_name ?? '',
                    $complaint->created_at?->format('Y-m-d') ?? '',
                    $completion?->format('Y-m-d') ?? '',
                    $completion && $complaint->created_at
                        ? number_format(max(0, $complaint->created_at->diffInSeconds($completion)) / 86400, 1, '.', '')
                        : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function categoryLabel(?string $key): string
    {
        if ($key === null || $key === '') {
            return __('analytics.empty.category');
        }

        return \App\Services\DepartmentRouter::categoryLabels()[$key]
            ?? Str::of($key)->replace('_', ' ')->title()->toString();
    }

    /**
     * The core counts for one office and one period.
     *
     * @return array<string, mixed>
     */
    public function metrics(Department $department, AnalyticsPeriod $period): array
    {
        $current = $this->periodMetrics($department, $period);
        $previous = $this->periodMetrics($department, $period->previous());

        $openQuery = Complaint::query()
            ->where('department_id', $department->id)
            ->whereIn('status', self::OPEN_STATUSES);

        $open = (clone $openQuery)->count();
        $assigned = (clone $openQuery)->whereNotNull('assigned_staff_id')->count();
        $staffCount = $department->staff()->count();

        return array_merge($current, [
            'open' => $open,
            'assigned_open' => $assigned,
            'unassigned_open' => $open - $assigned,
            'overdue' => count($this->sla->overdueIds($department->id)),
            'staff_count' => $staffCount,
            'per_staff' => $staffCount > 0 ? round($open / $staffCount, 1) : null,
            'previous' => $previous,
        ]);
    }

    /**
     * Counts that belong to a single window: filed, resolved, speed, on-time.
     *
     * @return array{filed: int, resolved: int, avg_days: float|null, on_time: int, on_time_base: int, on_time_rate: float|null}
     */
    private function periodMetrics(Department $department, AnalyticsPeriod $period): array
    {
        $filed = $this->filedQuery($department, $period)->count();

        $completed = $this->completedQuery($department, $period)->get([
            'id',
            'status',
            'created_at',
            'verified_at',
            'resolved_at',
            'closed_at',
            'updated_at',
            'confirmed_priority',
            'suggested_priority',
        ]);

        $durations = [];
        $onTime = 0;
        $onTimeBase = 0;

        foreach ($completed as $complaint) {
            $completion = $this->completionOf($complaint);

            if ($completion !== null && $complaint->created_at !== null) {
                $durations[] = max(0, $complaint->created_at->diffInSeconds($completion)) / 86400;
            }

            if ($complaint->verified_at !== null && $completion !== null) {
                $onTimeBase++;

                $priority = $complaint->confirmed_priority
                    ?: $complaint->suggested_priority
                    ?: 'routine';

                $deadline = CarbonImmutable::parse($complaint->verified_at)
                    ->addDays($this->sla->resolveDays($priority));

                if ($completion->lte($deadline)) {
                    $onTime++;
                }
            }
        }

        return [
            'filed' => $filed,
            'resolved' => $completed->count(),
            'avg_days' => $durations === []
                ? null
                : round(array_sum($durations) / count($durations), 1),
            'on_time' => $onTime,
            'on_time_base' => $onTimeBase,
            'on_time_rate' => $onTimeBase > 0
                ? round(($onTime / $onTimeBase) * 100, 1)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function totals(Collection $rows): array
    {
        $sum = fn (string $key): int => (int) $rows->sum(
            fn (array $row): int => (int) ($row['metrics'][$key] ?? 0)
        );

        $previousSum = fn (string $key): int => (int) $rows->sum(
            fn (array $row): int => (int) ($row['metrics']['previous'][$key] ?? 0)
        );

        $resolved = $sum('resolved');
        $weightedDays = $rows->sum(
            fn (array $row): float => (float) ($row['metrics']['avg_days'] ?? 0) * (int) $row['metrics']['resolved']
        );

        $onTime = $sum('on_time');
        $onTimeBase = $sum('on_time_base');

        return [
            'offices' => $rows->count(),
            'staff_count' => $sum('staff_count'),
            'filed' => $sum('filed'),
            'resolved' => $resolved,
            'open' => $sum('open'),
            'overdue' => $sum('overdue'),
            'avg_days' => $resolved > 0 ? round($weightedDays / $resolved, 1) : null,
            'on_time' => $onTime,
            'on_time_base' => $onTimeBase,
            'on_time_rate' => $onTimeBase > 0 ? round(($onTime / $onTimeBase) * 100, 1) : null,
            'previous' => [
                'filed' => $previousSum('filed'),
                'resolved' => $previousSum('resolved'),
            ],
            'per_staff' => $sum('staff_count') > 0
                ? round($sum('open') / $sum('staff_count'), 1)
                : null,
        ];
    }

    /**
     * The status of the reports filed in the period, as a donut.
     *
     * @return array{total: int, segments: list<array{key: string, label: string, count: int, percent: float}>}
     */
    private function statusMix(Builder $filedQuery): array
    {
        $counts = (clone $filedQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $segments = [];

        foreach (ComplaintStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);

            $segments[] = [
                'key' => Str::slug($status->value, '_'),
                'label' => $status->label(),
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * Verification and quality, measured on the reports filed in the period.
     *
     * @return array<string, mixed>
     */
    private function quality(Department $department, AnalyticsPeriod $period, int $filed): array
    {
        $query = $this->filedQuery($department, $period);

        $reviewCounts = (clone $query)
            ->selectRaw('review_status, COUNT(*) as total')
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        $verified = (int) ($reviewCounts[ComplaintReviewStatus::Verified->value] ?? 0);
        $needsInformation = (int) ($reviewCounts[ComplaintReviewStatus::NeedsInformation->value] ?? 0);
        $duplicate = (int) ($reviewCounts[ComplaintReviewStatus::Duplicate->value] ?? 0);
        $rejected = (int) (clone $query)->where('status', ComplaintStatus::Rejected->value)->count();

        return [
            'filed' => $filed,
            'verified' => $verified,
            'verified_rate' => $filed > 0 ? round(($verified / $filed) * 100, 1) : null,
            'needs_information' => $needsInformation,
            'duplicate' => $duplicate,
            'rejected' => $rejected,
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, percent: float}>
     */
    private function categories(Department $department, AnalyticsPeriod $period, int $filed): array
    {
        return $this->filedQuery($department, $period)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->orderBy('category')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) $row->category,
                'label' => self::categoryLabel($row->category),
                'count' => (int) $row->total,
                'percent' => $filed > 0 ? round(((int) $row->total / $filed) * 100, 1) : 0.0,
            ])
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, count: int, percent: float}>
     */
    private function barangays(Department $department, AnalyticsPeriod $period, int $filed): array
    {
        return $this->filedQuery($department, $period)
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->selectRaw('barangay, COUNT(*) as total')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->orderBy('barangay')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) $row->barangay,
                'label' => (string) $row->barangay,
                'count' => (int) $row->total,
                'percent' => $filed > 0 ? round(((int) $row->total / $filed) * 100, 1) : 0.0,
            ])
            ->all();
    }

    /**
     * How long the work that is open right now has waited.
     *
     * @return list<array{key: string, count: int}>
     */
    private function aging(Department $department): array
    {
        $buckets = ['0_3' => 0, '4_7' => 0, '8_14' => 0, '15_plus' => 0];

        Complaint::query()
            ->where('department_id', $department->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->get(['id', 'created_at'])
            ->each(function (Complaint $complaint) use (&$buckets): void {
                $days = $complaint->created_at
                    ? (int) floor(max(0, $complaint->created_at->diffInSeconds(now())) / 86400)
                    : 0;

                $key = match (true) {
                    $days <= 3 => '0_3',
                    $days <= 7 => '4_7',
                    $days <= 14 => '8_14',
                    default => '15_plus',
                };

                $buckets[$key]++;
            });

        return collect($buckets)
            ->map(fn (int $count, string $key): array => ['key' => $key, 'count' => $count])
            ->values()
            ->all();
    }

    /**
     * Filed and resolved per trend bucket.
     *
     * @return array{granularity: string, rows: list<array{label: string, filed: int, resolved: int}>, max: int}
     */
    private function trend(Department $department, AnalyticsPeriod $period): array
    {
        $filedByDay = (clone $this->filedQuery($department, $period))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $resolvedByDay = $this->completedQuery($department, $period)
            ->selectRaw('DATE('.self::COMPLETION_SQL.') as day, COUNT(*) as total')
            ->groupByRaw('DATE('.self::COMPLETION_SQL.')')
            ->pluck('total', 'day');

        $rows = [];

        foreach ($period->buckets() as $bucket) {
            $rows[] = [
                'label' => $bucket['label'],
                'filed' => 0,
                'resolved' => 0,
            ];
        }

        $this->addDaysToBuckets($rows, $period, $filedByDay->all(), 'filed');
        $this->addDaysToBuckets($rows, $period, $resolvedByDay->all(), 'resolved');

        $max = max(1, ...array_map(
            fn (array $row): int => max($row['filed'], $row['resolved']),
            $rows,
        ));

        return [
            'granularity' => $period->granularity(),
            'rows' => $rows,
            'max' => $max,
        ];
    }

    /**
     * @param  list<array{label: string, filed: int, resolved: int}>  $rows
     * @param  array<string, int|string>  $counts
     */
    private function addDaysToBuckets(array &$rows, AnalyticsPeriod $period, array $counts, string $key): void
    {
        $buckets = $period->buckets();

        foreach ($counts as $day => $count) {
            $date = CarbonImmutable::parse((string) $day)->startOfDay();

            foreach ($buckets as $index => $bucket) {
                if ($date->betweenIncluded($bucket['start'], $bucket['end'])) {
                    $rows[$index][$key] += (int) $count;
                    break;
                }
            }
        }
    }

    /**
     * The latest updates on this office's reports, inside the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function activity(Department $department, AnalyticsPeriod $period): Collection
    {
        return ComplaintLog::query()
            ->with([
                'actor:id,first_name,last_name,role,display_alias',
                'complaint:id,ticket_id,title,department_id',
            ])
            ->whereHas('complaint', fn (Builder $query) => $query->where('department_id', $department->id))
            ->whereBetween('created_at', [$period->start, $period->end])
            ->latest('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (ComplaintLog $log): array {
                $from = $log->previous_status
                    ? (ComplaintStatus::tryFrom((string) $log->previous_status)?->label() ?? (string) $log->previous_status)
                    : null;
                $to = $log->new_status
                    ? (ComplaintStatus::tryFrom((string) $log->new_status)?->label() ?? (string) $log->new_status)
                    : null;

                return [
                    'id' => $log->id,
                    'ticket_id' => $log->complaint?->ticket_id,
                    'title' => $log->complaint?->title,
                    'complaint_id' => $log->complaint_id,
                    'actor' => $log->actor?->staff_actor_name ?? __('analytics.activity.system'),
                    'from' => $from,
                    'to' => $to,
                    'is_internal' => (bool) $log->is_internal,
                    'comment' => $log->comment ? Str::limit($log->comment, 160) : null,
                    'created_at' => $log->created_at,
                    'action' => $from !== null
                        && $to !== null
                        && (string) $log->previous_status !== (string) $log->new_status
                        ? 'status_changed'
                        : ($log->previous_status === null && ! $log->is_internal
                            ? 'filed'
                            : ($log->is_internal ? 'note' : 'updated')),
                ];
            });
    }

    /**
     * Per-staff workload. Administrator-only by construction.
     *
     * @return list<array<string, mixed>>
     */
    private function staffWorkload(Department $department, AnalyticsPeriod $period): array
    {
        $staff = $department->staff()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $openCounts = Complaint::query()
            ->where('department_id', $department->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('assigned_staff_id')
            ->selectRaw('assigned_staff_id, COUNT(*) as total')
            ->groupBy('assigned_staff_id')
            ->pluck('total', 'assigned_staff_id');

        $progressCounts = Complaint::query()
            ->where('department_id', $department->id)
            ->where('status', ComplaintStatus::InProgress->value)
            ->whereNotNull('assigned_staff_id')
            ->selectRaw('assigned_staff_id, COUNT(*) as total')
            ->groupBy('assigned_staff_id')
            ->pluck('total', 'assigned_staff_id');

        $resolvedCounts = $this->completedQuery($department, $period)
            ->whereNotNull('assigned_staff_id')
            ->selectRaw('assigned_staff_id, COUNT(*) as total')
            ->groupBy('assigned_staff_id')
            ->pluck('total', 'assigned_staff_id');

        $overdueIds = $this->sla->overdueIds($department->id);
        $overdueCounts = $overdueIds === []
            ? collect()
            : Complaint::query()
                ->whereIn('id', $overdueIds)
                ->whereNotNull('assigned_staff_id')
                ->selectRaw('assigned_staff_id, COUNT(*) as total')
                ->groupBy('assigned_staff_id')
                ->pluck('total', 'assigned_staff_id');

        $completedByStaff = $this->completedQuery($department, $period)
            ->whereNotNull('assigned_staff_id')
            ->get(['assigned_staff_id', 'status', 'created_at', 'resolved_at', 'closed_at', 'updated_at'])
            ->groupBy('assigned_staff_id')
            ->map(function (Collection $group): ?float {
                $durations = $group->map(function (Complaint $complaint): ?float {
                    $completion = $this->completionOf($complaint);

                    return $completion && $complaint->created_at
                        ? max(0, $complaint->created_at->diffInSeconds($completion)) / 86400
                        : null;
                })->filter(fn (?float $value): bool => $value !== null);

                return $durations->isEmpty() ? null : round($durations->avg(), 1);
            });

        $rows = $staff->map(fn ($member): array => [
            'id' => $member->id,
            'name' => $member->full_name,
            'open' => (int) ($openCounts[$member->id] ?? 0),
            'in_progress' => (int) ($progressCounts[$member->id] ?? 0),
            'resolved' => (int) ($resolvedCounts[$member->id] ?? 0),
            'overdue' => (int) ($overdueCounts[$member->id] ?? 0),
            'avg_days' => $completedByStaff[$member->id] ?? null,
        ])->values()->all();

        $unassignedOpen = Complaint::query()
            ->where('department_id', $department->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereNull('assigned_staff_id')
            ->count();

        if ($unassignedOpen > 0) {
            $rows[] = [
                'id' => null,
                'name' => __('analytics.workload.table.unassigned'),
                'open' => $unassignedOpen,
                'in_progress' => Complaint::query()
                    ->where('department_id', $department->id)
                    ->where('status', ComplaintStatus::InProgress->value)
                    ->whereNull('assigned_staff_id')
                    ->count(),
                'resolved' => $this->completedQuery($department, $period)
                    ->whereNull('assigned_staff_id')
                    ->count(),
                'overdue' => Complaint::query()
                    ->whereIn('id', $overdueIds)
                    ->whereNull('assigned_staff_id')
                    ->count(),
                'avg_days' => null,
            ];
        }

        return $rows;
    }

    private function filedQuery(Department $department, AnalyticsPeriod $period): Builder
    {
        return Complaint::query()
            ->where('department_id', $department->id)
            ->whereBetween('created_at', [$period->start, $period->end]);
    }

    private function completedQuery(Department $department, AnalyticsPeriod $period): Builder
    {
        return Complaint::query()
            ->where('department_id', $department->id)
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->whereRaw(self::COMPLETION_SQL.' BETWEEN ? AND ?', [
                $period->start->toDateTimeString(),
                $period->end->toDateTimeString(),
            ]);
    }

    private function completionOf(Complaint $complaint): ?CarbonImmutable
    {
        if (! in_array($complaint->status, self::COMPLETED_STATUSES, true)) {
            return null;
        }

        $value = $complaint->resolved_at ?? $complaint->closed_at ?? $complaint->updated_at;

        return $value !== null ? CarbonImmutable::parse($value) : null;
    }
}
