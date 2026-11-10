<?php

namespace App\Services;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintSlaState;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The clock on the work a department owes.
 *
 * A report becomes the office's responsibility the moment it is verified.
 * From then the office owes the resident two things: to start the work, and
 * to finish it. This service says when each is due, how the complaint stands
 * against them, and which complaints have slipped — so a ticket nobody
 * started does not sit in the queue looking exactly like a ticket being
 * worked on today.
 *
 * The deadlines are per priority and admin-editable (system settings
 * `sla_start_work_days` and `sla_resolve_days`, each a JSON map of priority
 * to days with a `default`). A critical pothole is not owed the same
 * patience as a routine one.
 */
class ComplaintSlaService
{
    public const SETTING_START = 'sla_start_work_days';

    public const SETTING_RESOLVE = 'sla_resolve_days';

    /**
     * Days allowed before the work must have started, by priority.
     */
    public const DEFAULT_START_DAYS = [
        'default' => 3,
        'critical' => 1,
        'urgent' => 1,
        'elevated' => 2,
        'routine' => 3,
    ];

    /**
     * Days allowed before the work must have finished, by priority.
     */
    public const DEFAULT_RESOLVE_DAYS = [
        'default' => 14,
        'critical' => 3,
        'urgent' => 7,
        'elevated' => 10,
        'routine' => 14,
    ];

    /** A deadline this close is "due soon". */
    public const DUE_SOON_DAYS = 1;

    /**
     * How many days the office has for this complaint's priority.
     */
    public function startDays(?string $priority = null): int
    {
        return $this->daysFor(self::SETTING_START, self::DEFAULT_START_DAYS, $priority);
    }

    /**
     * How many days the office has to finish this complaint's priority.
     */
    public function resolveDays(?string $priority = null): int
    {
        return $this->daysFor(self::SETTING_RESOLVE, self::DEFAULT_RESOLVE_DAYS, $priority);
    }

    /**
     * The day the work must have started by.
     */
    public function startDeadline(Complaint $complaint): ?Carbon
    {
        return $this->deadline($complaint, $this->startDays($this->priorityOf($complaint)));
    }

    /**
     * The day the work must have finished by.
     */
    public function resolveDeadline(Complaint $complaint): ?Carbon
    {
        return $this->deadline($complaint, $this->resolveDays($this->priorityOf($complaint)));
    }

    /**
     * Where the complaint stands, and what the office owes it right now.
     *
     * @return array{
     *     state: ComplaintSlaState,
     *     action: string|null,
     *     days: int|null,
     *     deadline: Carbon|null
     * }
     */
    public function summary(Complaint $complaint): array
    {
        // Nothing is owed until the office has accepted the report.
        if (! $complaint->isVerified() || $complaint->verified_at === null) {
            return [
                'state' => ComplaintSlaState::NotApplicable,
                'action' => 'verify',
                'days' => null,
                'deadline' => null,
            ];
        }

        $status = $complaint->statusEnum;

        // Finished, or thrown out — the obligation is discharged either way.
        if (in_array($status, [ComplaintStatus::Resolved, ComplaintStatus::Rejected, ComplaintStatus::Closed], true)) {
            return [
                'state' => ComplaintSlaState::Met,
                'action' => null,
                'days' => null,
                'deadline' => null,
            ];
        }

        $started = $status === ComplaintStatus::InProgress;

        $deadline = $started
            ? $this->resolveDeadline($complaint)
            : $this->startDeadline($complaint);

        $days = $this->daysRemaining($deadline);

        $state = match (true) {
            $days === null => ComplaintSlaState::NotApplicable,
            $days < 0 => ComplaintSlaState::Overdue,
            $days <= self::DUE_SOON_DAYS => ComplaintSlaState::DueSoon,
            default => ComplaintSlaState::OnTrack,
        };

        return [
            'state' => $state,
            'action' => $started ? 'complete_work' : 'start_work',
            'days' => $days,
            'deadline' => $deadline,
        ];
    }

    /**
     * The complaints that have slipped past a deadline.
     *
     * The days depend on the priority, so the deadline is a CASE over the
     * priority rather than a single interval. Only the ids come back — the
     * queue filters on them, and every row still reads its own state.
     *
     * @return array<int, int>
     */
    public function overdueIds(?int $departmentId = null): array
    {
        $startCase = $this->intervalCase(
            $this->dayMap(self::SETTING_START, self::DEFAULT_START_DAYS)
        );
        $resolveCase = $this->intervalCase(
            $this->dayMap(self::SETTING_RESOLVE, self::DEFAULT_RESOLVE_DAYS)
        );

        return Complaint::query()
            ->where('review_status', ComplaintReviewStatus::Verified->value)
            ->whereNotNull('verified_at')
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])
            ->when(
                $departmentId,
                fn ($query) => $query->where('department_id', $departmentId)
            )
            ->where(function (Builder $query) use ($startCase, $resolveCase): void {
                $query->where(function (Builder $inner) use ($startCase): void {
                    $inner->whereIn('status', [
                        ComplaintStatus::Submitted->value,
                        ComplaintStatus::UnderReview->value,
                    ])->whereRaw("verified_at <= now() - ({$startCase})");
                })->orWhere(function (Builder $inner) use ($resolveCase): void {
                    $inner->where('status', ComplaintStatus::InProgress->value)
                        ->whereRaw("verified_at <= now() - ({$resolveCase})");
                });
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * How many complaints in this department are overdue.
     */
    public function overdueCount(?int $departmentId = null): int
    {
        return count($this->overdueIds($departmentId));
    }

    /**
     * The priority the deadline is measured against.
     */
    private function priorityOf(Complaint $complaint): string
    {
        return $complaint->confirmedPriorityEnum?->value
            ?? $complaint->suggested_priority
            ?? 'routine';
    }

    private function deadline(Complaint $complaint, int $days): ?Carbon
    {
        if ($complaint->verified_at === null) {
            return null;
        }

        return Carbon::parse($complaint->verified_at)->addDays($days);
    }

    /**
     * Whole days until the deadline. Negative means it has passed, and counts
     * how many whole days it has been past.
     */
    private function daysRemaining(?Carbon $deadline): ?int
    {
        if ($deadline === null) {
            return null;
        }

        $seconds = now()->diffInSeconds($deadline, false);

        return $seconds >= 0
            ? (int) floor($seconds / 86400)
            : (int) ceil($seconds / 86400);
    }

    /**
     * The days for one priority, from the setting or the default.
     */
    private function daysFor(string $setting, array $defaults, ?string $priority): int
    {
        $map = $this->dayMap($setting, $defaults);
        $priority = $priority ?? 'routine';

        return (int) ($map[$priority] ?? $map['default']);
    }

    /**
     * The setting, merged over the defaults and sanitised.
     *
     * The setting is admin-editable, so the priority keys are checked against
     * a whitelist and the days are cast to whole numbers before they ever
     * reach a query.
     *
     * @return array<string, int>
     */
    private function dayMap(string $setting, array $defaults): array
    {
        $raw = SystemSetting::getValue($setting);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($decoded)) {
            return $defaults;
        }

        $allowed = array_merge(['default'], array_map(
            fn (ComplaintPriority $priority): string => $priority->value,
            ComplaintPriority::cases()
        ));

        $map = $defaults;

        foreach ($decoded as $priority => $days) {
            if (! is_string($priority) || ! in_array($priority, $allowed, true)) {
                continue;
            }

            if (! is_numeric($days)) {
                continue;
            }

            $map[$priority] = max(0, (int) $days);
        }

        return $map;
    }

    /**
     * A SQL CASE that yields the interval for each priority.
     *
     * @param  array<string, int>  $map
     */
    private function intervalCase(array $map): string
    {
        $case = 'case confirmed_priority';

        foreach ($map as $priority => $days) {
            if ($priority === 'default') {
                continue;
            }

            $case .= " when '{$priority}' then interval '{$days} day'";
        }

        $case .= " else interval '{$map['default']} day' end";

        return $case;
    }
}
