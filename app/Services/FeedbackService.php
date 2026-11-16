<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\FeedbackStatus;
use App\Models\Department;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resident feedback: the ratings and the suggestions.
 *
 * A rating counts from the moment it is given, and it is final — a resident
 * rates an office once, so the number cannot be quietly moved later. A
 * suggestion waits for the administration to read it, because a suggestion is
 * prose about the portal and prose goes out reviewed.
 *
 * The offices a resident can rate are the ones that have actually finished a
 * report for them: resolved or closed. A rating of an office you never dealt
 * with says nothing about the office, and a rating of work still in progress
 * says nothing about the finished service.
 */
class FeedbackService
{
    /**
     * How many offices a resident is offered at once.
     */
    public const MAX_OFFICES = 8;

    /**
     * The municipality's day. "One suggestion a day" means the day a Daet
     * resident lives in, not the UTC day the server stores timestamps in.
     */
    public const DAY_TIMEZONE = 'Asia/Manila';

    /**
     * The offices that have resolved or closed a report for this resident,
     * most recent first.
     *
     * @return Collection<int, Department>
     */
    public function officesFor(User $user): Collection
    {
        // The offices that finished a report for this resident, most recent
        // first. The department id and the date it was last touched are
        // selected together: Postgres will not order a DISTINCT query by a
        // column it did not select.
        $recent = DB::table('complaints')
            ->where('user_id', $user->id)
            ->whereNotNull('department_id')
            ->whereIn('status', [
                ComplaintStatus::Resolved->value,
                ComplaintStatus::Closed->value,
            ])
            ->select('department_id', DB::raw('MAX(updated_at) as last_touched'))
            ->groupBy('department_id')
            ->orderByDesc('last_touched')
            ->limit(self::MAX_OFFICES)
            ->pluck('department_id');

        return Department::query()
            ->whereIn('id', $recent)
            ->orderBy('name')
            ->get();
    }

    /**
     * Record a rating of the portal or of an office — once.
     *
     * A rating is final: the first one stands, and a later submit for the same
     * scope returns that same row unchanged. The partial unique indexes in the
     * migration make the same promise at the database, and createOrFirst turns
     * the losing side of a simultaneous submit into the existing row.
     */
    public function recordRating(User $user, ?Department $department, int $rating): Feedback
    {
        return Feedback::query()->createOrFirst(
            [
                'user_id' => $user->id,
                'kind' => Feedback::KIND_RATING,
                'department_id' => $department?->id,
            ],
            [
                'rating' => $rating,
                'status' => FeedbackStatus::Published->value,
                'published_at' => now(),
                'reviewed_at' => now(),
            ],
        );
    }

    /**
     * A suggestion, waiting to be read.
     */
    public function submitSuggestion(User $user, string $body): Feedback
    {
        return Feedback::create([
            'user_id' => $user->id,
            'kind' => Feedback::KIND_SUGGESTION,
            'body' => $body,
            'status' => FeedbackStatus::Pending->value,
        ]);
    }

    /**
     * Whether this resident has already used today's suggestion.
     *
     * Counts every suggestion sent since midnight in Daet, whatever its
     * status: publishing or withholding it does not give the day back.
     */
    public function hasSuggestedToday(User $user): bool
    {
        $startOfDay = now(self::DAY_TIMEZONE)->startOfDay()->utc();

        return Feedback::query()
            ->where('user_id', $user->id)
            ->where('kind', Feedback::KIND_SUGGESTION)
            ->where('created_at', '>=', $startOfDay)
            ->exists();
    }

    /**
     * The administration reads a suggestion and puts it on the register — or
     * keeps it off.
     */
    public function review(Feedback $feedback, User $reviewer, bool $publish): Feedback
    {
        $feedback->update([
            'status' => $publish ? FeedbackStatus::Published->value : FeedbackStatus::Withheld->value,
            'published_at' => $publish ? now() : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $feedback->fresh();
    }

    /**
     * The average rating for a scope: the portal (null) or one office.
     *
     * @return array{average: float|null, count: int}
     */
    public function averageFor(?Department $department): array
    {
        $query = Feedback::query()
            ->published()
            ->ratings()
            ->where('department_id', $department?->id);

        $row = $query->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();

        $count = (int) ($row->total ?? 0);
        $average = $row->average !== null ? round((float) $row->average, 1) : null;

        return [
            'average' => $average,
            'count' => $count,
        ];
    }

    /**
     * The average for every office that has been rated.
     *
     * @return array<int, array{department: Department, average: float|null, count: int}>
     */
    public function officeAverages(): array
    {
        $rows = Feedback::query()
            ->published()
            ->ratings()
            ->whereNotNull('department_id')
            ->selectRaw('department_id, COUNT(*) as total, AVG(rating) as average')
            ->groupBy('department_id')
            ->get();

        $offices = Department::whereIn('id', $rows->pluck('department_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($offices): array {
            $department = $offices->get((int) $row->department_id);

            return [
                'department' => $department,
                'average' => $row->average !== null ? round((float) $row->average, 1) : null,
                'count' => (int) $row->total,
            ];
        })->filter(fn (array $row): bool => $row['department'] !== null)->values()->all();
    }
}
