<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Services\TransparencyFeed;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Serves the two public transparency pages.
 *
 * The index renders no data of its own. It hands back the page shell, and the
 * register inside that shell is a Livewire component which re-reads the database
 * on a timer — that is why the query that used to live in index() now lives in
 * TransparencyFeed, shared with the component.
 *
 * The detail page is still a plain, one-shot render, and deliberately so: it is
 * a single complaint's published record. Nothing about it is shared with the
 * register, so there is nothing for a poll to keep in step.
 */
class TransparencyController extends Controller
{
    /**
     * The public register. The component does the reading; see TransparencyFeed
     * for the query and for why that logic is shared rather than duplicated.
     */
    public function index()
    {
        return view('pages.transparency.index');
    }

    public function show(Complaint $complaint, TransparencyFeed $feed): View
    {
        $this->ensurePublicComplaint($complaint);

        $complaint->load(['department', 'user']);
        $logs = $complaint->logs()
            ->where('is_internal', false)
            ->reorder('created_at')
            ->get();
        $reporter = trim((string) $complaint->user?->display_alias);

        return view('pages.transparency.show', [
            'complaint' => $complaint,
            'logs' => $logs,
            'location' => $feed->publicLocation($complaint),
            'description' => $this->publicDescription($complaint),
            'categoryLabel' => $feed->categoryLabel($complaint->category),
            'reporter' => $reporter !== '' ? $reporter : 'Community member',
            'progressSteps' => $this->progressSteps($complaint, $logs),
        ]);
    }

    private function ensurePublicComplaint(Complaint $complaint): void
    {
        if (! $complaint->isPubliclyVisible() || $complaint->spam_status === 'spam') {
            abort(404);
        }
    }

    /**
     * Build a compact, date-aware progress trail for a public complaint.
     *
     * @param  Collection<int, \App\Models\ComplaintLog>  $logs
     * @return array<int, array{status: string, label: string, date: string|null, state: string}>
     */
    private function progressSteps(Complaint $complaint, Collection $logs): array
    {
        $statuses = [
            ComplaintStatus::Submitted,
            ComplaintStatus::UnderReview,
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
            ComplaintStatus::Closed,
        ];
        $current = $complaint->statusEnum;
        $currentIndex = (int) array_search($current, $statuses, true);
        $rejected = $current === ComplaintStatus::Rejected;

        if ($rejected) {
            $lastProgressLog = $logs->reject(fn ($log): bool => $log->new_status === ComplaintStatus::Rejected->value)->last();
            $lastReached = $lastProgressLog?->new_status;
            $currentIndex = max(0, (int) array_search($lastReached, $statuses, true));
        }

        return collect($statuses)->map(function (ComplaintStatus $status, int $index) use ($logs, $complaint, $currentIndex, $rejected): array {
            $log = $logs->first(fn ($entry): bool => $entry->new_status === $status->value);
            $state = match (true) {
                $rejected && $index < $currentIndex => 'complete',
                $rejected && $index === $currentIndex => 'rejected',
                $rejected => 'stopped',
                $index < $currentIndex => 'complete',
                $index === $currentIndex => 'current',
                default => 'upcoming',
            };

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'date' => $log?->created_at?->format('M j, Y')
                    ?? ($status === ComplaintStatus::Submitted ? $complaint->created_at?->format('M j, Y') : null),
                'state' => $state,
            ];
        })->all();
    }

    /**
     * The description the public may read.
     *
     * A staff redaction, when present, replaces the text outright — it is a
     * deliberate decision about what may be broadcast and must not be undone by
     * the automatic address scrub below. With no staff redaction, the exact
     * location is still stripped, because a precise address is a privacy risk
     * in its own right and has always been withheld from the public page.
     */
    private function publicDescription(Complaint $complaint): string
    {
        $description = trim((string) $complaint->publicDescription());

        if ($description === '') {
            return 'No public description was provided.';
        }

        $address = trim((string) $complaint->address_text);

        if ($address === '') {
            return $description;
        }

        $redacted = preg_replace(
            '/'.preg_quote($address, '/').'/iu',
            'Exact location withheld',
            $description,
        );

        return is_string($redacted) ? $redacted : $description;
    }
}