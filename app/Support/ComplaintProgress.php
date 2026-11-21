<?php

namespace App\Support;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use Illuminate\Support\Collection;

/**
 * The one place that turns a complaint and its public log into the five-step
 * progress trail.
 *
 * The public transparency detail, the resident ticket and the guest tracker all
 * answer the same question, so they must not each carry their own copy of the
 * status-to-step mapping. The helper only reads; callers decide which logs are
 * allowed to reach it (the public views pass `is_internal = false`).
 */
final class ComplaintProgress
{
    /** @var array<int, ComplaintStatus> */
    private const STEPS = [
        ComplaintStatus::Submitted,
        ComplaintStatus::UnderReview,
        ComplaintStatus::InProgress,
        ComplaintStatus::Resolved,
        ComplaintStatus::Closed,
    ];

    /**
     * Build a compact, date-aware progress trail.
     *
     * @param  Collection<int, ComplaintLog>  $logs
     * @return array<int, array{status: string, label: string, date: string|null, state: string}>
     */
    public static function steps(Complaint $complaint, Collection $logs): array
    {
        $logs = $logs
            ->filter(fn (mixed $log): bool => $log instanceof ComplaintLog)
            ->sortByDesc(fn (ComplaintLog $log): string => sprintf(
                '%020d-%010d',
                $log->created_at?->getTimestamp() ?? 0,
                $log->id,
            ))
            ->values();

        $current = $complaint->statusEnum;
        $rejected = $current === ComplaintStatus::Rejected;
        $closed = $current === ComplaintStatus::Closed;

        if ($closed) {
            $currentIndex = count(self::STEPS); // every step is done
        } elseif ($rejected) {
            // The rejection log records where the complaint was before it was
            // stopped. Using `previous_status` is deliberate: the old code
            // took the oldest non-rejected log from a newest-first collection,
            // so a rejection after In Progress was drawn as stuck at Submitted.
            $rejectionLog = $logs->first(
                fn (ComplaintLog $log): bool => $log->new_status === ComplaintStatus::Rejected->value
            );
            $lastReached = $rejectionLog?->previous_status
                ?? $logs->first(
                    fn (ComplaintLog $log): bool => $log->new_status !== ComplaintStatus::Rejected->value
                )?->new_status;
            $currentIndex = self::indexOf($lastReached);
        } else {
            $currentIndex = self::indexOf($current->value);
        }

        $lastDate = null;

        return array_map(function (ComplaintStatus $status, int $index) use (
            $logs,
            $complaint,
            $currentIndex,
            $rejected,
            &$lastDate,
        ): array {
            $log = $logs->first(fn (ComplaintLog $entry): bool => $entry->new_status === $status->value);

            $state = match (true) {
                $rejected && $index < $currentIndex => 'complete',
                $rejected && $index === $currentIndex => 'rejected',
                $rejected => 'stopped',
                $index < $currentIndex => 'complete',
                $index === $currentIndex => 'current',
                default => 'upcoming',
            };

            $rawDate = $status === ComplaintStatus::Submitted
                ? $complaint->created_at
                : $log?->created_at;

            $formatted = null;
            if ($rawDate !== null && ($lastDate === null || $rawDate->greaterThanOrEqualTo($lastDate))) {
                $formatted = $rawDate->locale(app()->getLocale())->translatedFormat('M j, Y');
                $lastDate = $rawDate;
            }

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'date' => $formatted,
                'state' => $state,
            ];
        }, self::STEPS, array_keys(self::STEPS));
    }

    /**
     * One plain-language sentence explaining the current stage.
     */
    public static function whatMeans(Complaint $complaint): string
    {
        $office = $complaint->department?->name ?? __('transparency.show.department_pending');

        return match ($complaint->statusEnum) {
            ComplaintStatus::Submitted => __('transparency.show.what_submitted'),
            ComplaintStatus::UnderReview => __('transparency.show.what_under_review'),
            ComplaintStatus::InProgress => __('transparency.show.what_in_progress', ['office' => $office]),
            ComplaintStatus::Resolved => __('transparency.show.what_resolved'),
            ComplaintStatus::Closed => __('transparency.show.what_closed'),
            ComplaintStatus::Rejected => __('transparency.show.what_rejected'),
        };
    }

    private static function indexOf(?string $status): int
    {
        if ($status === null) {
            return 0;
        }

        foreach (self::STEPS as $index => $step) {
            if ($step->value === $status) {
                return $index;
            }
        }

        return 0;
    }
}
