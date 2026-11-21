<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use App\Support\AnalyticsPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffPerformanceService
{
    /**
     * @return array{
     *   staff: User,
     *   period: AnalyticsPeriod,
     *   metrics: array<string, int|string|null>,
     *   activities: Collection<int, \stdClass>,
     *   complaints: Collection<int, Complaint>
     * }
     */
    public function report(User $staff, AnalyticsPeriod $period): array
    {
        $departmentId = $staff->department_id;

        $logsQuery = ComplaintLog::query()
            ->with([
                'complaint:id,ticket_id,title,category,department_id,status,created_at,resolved_at,updated_at',
                'actor:id,first_name,last_name,display_alias,role',
            ])
            ->where('actor_id', $staff->id)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->when($departmentId, fn ($q) => $q->whereHas('complaint', fn ($cq) => $cq->where('department_id', $departmentId)))
            ->latest('created_at');

        $logs = $logsQuery->get();

        $complaintIds = $logs->pluck('complaint_id')->filter()->unique()->values();

        $complaints = Complaint::query()
            ->whereIn('id', $complaintIds)
            ->with('department:id,name,code')
            ->orderByDesc('updated_at')
            ->get();

        $actions = $logs->count();
        $complaintsTouched = $complaintIds->count();
        $statusChanges = $logs->filter(fn ($log) => filled($log->new_status))->count();
        $responses = $logs->filter(function ($log) {
            $comment = trim((string) ($log->comment ?? ''));

            return $comment !== '' && ! str_starts_with($comment, 'Assigned to');
        })->count();

        $resolvedByMe = ComplaintLog::query()
            ->where('actor_id', $staff->id)
            ->where('new_status', ComplaintStatus::Resolved->value)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->when($departmentId, fn ($q) => $q->whereHas('complaint', fn ($cq) => $cq->where('department_id', $departmentId)))
            ->distinct('complaint_id')
            ->count('complaint_id');

        $inProgressByMe = ComplaintLog::query()
            ->where('actor_id', $staff->id)
            ->where('new_status', ComplaintStatus::InProgress->value)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->when($departmentId, fn ($q) => $q->whereHas('complaint', fn ($cq) => $cq->where('department_id', $departmentId)))
            ->distinct('complaint_id')
            ->count('complaint_id');

        $activities = $logs->map(function (ComplaintLog $log): \stdClass {
            $entry = new \stdClass();
            $entry->created_at = $log->created_at;
            $entry->ticket_id = $log->complaint?->ticket_id ?? '—';
            $entry->complaint_title = $log->complaint?->title ?? '';
            $entry->category = (string) ($log->complaint?->category ?? '');
            $entry->previous_status = $log->old_status ? (ComplaintStatus::tryFrom((string) $log->old_status)?->label() ?? (string) $log->old_status) : null;
            $entry->new_status = $log->new_status ? (ComplaintStatus::tryFrom((string) $log->new_status)?->label() ?? (string) $log->new_status) : null;
            $entry->comment = trim((string) ($log->comment ?? ''));
            $entry->is_internal = (bool) $log->is_internal;

            return $entry;
        });

        return [
            'staff' => $staff->loadMissing('department:id,name,code'),
            'period' => $period,
            'metrics' => [
                'actions' => $actions,
                'complaints_touched' => $complaintsTouched,
                'resolved' => $resolvedByMe,
                'moved_to_in_progress' => $inProgressByMe,
                'status_changes' => $statusChanges,
                'responses' => $responses,
            ],
            'activities' => $activities,
            'complaints' => $complaints,
        ];
    }

    public function exportCsv(User $staff, AnalyticsPeriod $period): StreamedResponse
    {
        $report = $this->report($staff, $period);
        $filename = sprintf(
            'staff-performance-%s-%s-to-%s.csv',
            Str::slug($staff->full_name ?: 'staff'),
            $period->start->toDateString(),
            $period->end->toDateString(),
        );

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            $staff = $report['staff'];
            $period = $report['period'];
            $metrics = $report['metrics'];

            fputcsv($out, [__('staff.performance.export.report_title')]);
            fputcsv($out, [__('staff.performance.export.staff'), $staff->full_name]);
            fputcsv($out, [__('staff.performance.export.email'), $staff->email]);
            fputcsv($out, [__('staff.performance.export.department'), $staff->department?->name ?? '']);
            fputcsv($out, [__('staff.performance.export.period'), $period->rangeLabel()]);
            fputcsv($out, [__('staff.performance.export.generated_at'), now()->format('Y-m-d H:i')]);
            fputcsv($out, []);

            fputcsv($out, [__('staff.performance.metrics.actions'), $metrics['actions']]);
            fputcsv($out, [__('staff.performance.metrics.complaints_touched'), $metrics['complaints_touched']]);
            fputcsv($out, [__('staff.performance.metrics.resolved'), $metrics['resolved']]);
            fputcsv($out, [__('staff.performance.metrics.moved_to_in_progress'), $metrics['moved_to_in_progress']]);
            fputcsv($out, [__('staff.performance.metrics.status_changes'), $metrics['status_changes']]);
            fputcsv($out, [__('staff.performance.metrics.responses'), $metrics['responses']]);
            fputcsv($out, []);

            fputcsv($out, [
                __('staff.performance.export.date_time'),
                __('staff.performance.export.ticket'),
                __('staff.performance.export.complaint_title'),
                __('staff.performance.export.category'),
                __('staff.performance.export.from_status'),
                __('staff.performance.export.to_status'),
                __('staff.performance.export.action_note'),
                __('staff.performance.export.visibility'),
            ]);

            foreach ($report['activities'] as $activity) {
                fputcsv($out, [
                    $activity->created_at?->format('Y-m-d H:i') ?? '',
                    $activity->ticket_id,
                    $activity->complaint_title,
                    DepartmentAnalyticsService::categoryLabel($activity->category ?: null),
                    $activity->previous_status ?? '',
                    $activity->new_status ?? '',
                    $activity->comment,
                    $activity->is_internal ? __('staff.performance.export.internal') : __('staff.performance.export.visible'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
