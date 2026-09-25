<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Services\DepartmentRouter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransparencyController extends Controller
{
    /**
     * The public map is intentionally approximate. Two decimal places keeps
     * the heat signal useful without exposing an exact report coordinate.
     */
    private const MAP_PRECISION = 2;

    private const DAET_CENTER = [14.1122, 122.9553];

    public function index()
    {
        $publicComplaints = Complaint::query()
            ->public()
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('spam_status')
                    ->orWhere('spam_status', '!=', 'spam');
            })
            ->with(['department', 'user'])
            ->latest()
            ->get();

        $geotaggedComplaints = $publicComplaints->filter(
            fn (Complaint $complaint): bool => $this->hasValidCoordinates($complaint),
        );

        $reports = $publicComplaints
            ->map(fn (Complaint $complaint): array => $this->publicReport($complaint))
            ->values();

        $lastUpdated = $publicComplaints->max('created_at');

        return view('pages.transparency.index', [
            'reports' => $reports,
            'mapPoints' => $this->mapPoints($geotaggedComplaints),
            'mapCenter' => self::DAET_CENTER,
            'departmentOptions' => $reports->pluck('department')->filter()->unique()->sort()->values(),
            'categoryOptions' => $this->categoryOptions($reports),
            'statusOptions' => $this->statusOptions($reports),
            'lastUpdated' => $lastUpdated?->format('M j, Y') ?? 'No public reports yet',
        ]);
    }

    public function show(Complaint $complaint): View
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
            'location' => $this->publicLocation($complaint),
            'description' => $this->publicDescription($complaint),
            'categoryLabel' => $this->categoryLabel($complaint->category),
            'reporter' => $reporter !== '' ? $reporter : 'Community member',
            'progressSteps' => $this->progressSteps($complaint, $logs),
        ]);
    }

    /**
     * Build coarse heat points without sending exact coordinates to the page.
     */
    private function mapPoints(Collection $complaints): Collection
    {
        return $complaints
            ->groupBy(function (Complaint $complaint): string {
                return sprintf(
                    '%.2f,%.2f',
                    (float) $complaint->latitude,
                    (float) $complaint->longitude,
                );
            })
            ->map(function (Collection $group): array {
                $count = $group->count();
                $first = $group->first();

                return [
                    'lat' => round((float) $first->latitude, self::MAP_PRECISION),
                    'lng' => round((float) $first->longitude, self::MAP_PRECISION),
                    'intensity' => min(1, 0.4 + (min($count, 5) * 0.12)),
                    'count' => $count,
                ];
            })
            ->values();
    }

    /**
     * Build a deliberately limited, public-safe representation of a report.
     */
    private function publicReport(Complaint $complaint): array
    {
        $status = $complaint->statusEnum;
        $category = $complaint->category;
        $alias = trim((string) $complaint->user?->display_alias);

        return [
            'ticket_id' => $complaint->ticket_id,
            'url' => route('transparency.complaints.show', $complaint),
            'title' => $complaint->title,
            'category' => $category,
            'category_label' => $this->categoryLabel($category),
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_key' => Str::slug($status->value, '_'),
            'department' => $complaint->department?->name ?? 'Department pending',
            'location' => $this->publicLocation($complaint),
            'reporter' => $alias !== '' ? $alias : 'Community member',
            'date' => $complaint->created_at?->format('M j, Y') ?? 'Date unavailable',
            'iso_date' => $complaint->created_at?->toAtomString() ?? '',
        ];
    }

    private function ensurePublicComplaint(Complaint $complaint): void
    {
        if (! $complaint->isPubliclyVisible() || $complaint->spam_status === 'spam') {
            abort(404);
        }
    }

    private function categoryLabel(string $category): string
    {
        return $this->categoryLabels()->get(
            $category,
            Str::headline(str_replace('_', ' ', $category)),
        );
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

    private function publicDescription(Complaint $complaint): string
    {
        $description = trim((string) $complaint->description);
        $address = trim((string) $complaint->address_text);

        if ($description === '' || $address === '') {
            return $description !== '' ? $description : 'No public description was provided.';
        }

        $redacted = preg_replace(
            '/'.preg_quote($address, '/').'/iu',
            'Exact location withheld',
            $description,
        );

        return is_string($redacted) ? $redacted : $description;
    }

    /**
     * Never publish a street address or exact coordinate in the public table.
     */
    private function publicLocation(Complaint $complaint): string
    {
        $address = trim((string) $complaint->address_text);

        if (preg_match('/(?:barangay|brgy\.?)\s*([^,]+)/i', $address, $matches)) {
            return 'Barangay '.trim($matches[1]).', Daet';
        }

        $barangay = trim((string) $complaint->user?->barangay);
        $barangay = preg_replace('/^barangay\s+/i', '', $barangay) ?? $barangay;

        return $barangay !== ''
            ? "Barangay {$barangay}, Daet"
            : 'Daet, Camarines Norte';
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    private function categoryOptions(Collection $reports): Collection
    {
        $labels = $this->categoryLabels();

        return $reports
            ->pluck('category')
            ->filter()
            ->unique()
            ->map(fn (string $category): array => [
                'key' => $category,
                'label' => $labels->get(
                    $category,
                    Str::headline(str_replace('_', ' ', $category)),
                ),
            ])
            ->sortBy('label')
            ->values();
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    private function statusOptions(Collection $reports): Collection
    {
        return collect(ComplaintStatus::cases())
            ->map(function (ComplaintStatus $status): array {
                $key = Str::slug($status->value, '_');

                return [
                    'key' => $key,
                    'label' => $status->label(),
                ];
            })
            ->filter(fn (array $option): bool => $reports->contains(
                'status_key',
                $option['key'],
            ))
            ->values();
    }

    /**
     * @return Collection<string, string>
     */
    private function categoryLabels(): Collection
    {
        return collect(DepartmentRouter::getCategoryOptions())
            ->flatten(1)
            ->pluck('label', 'value');
    }

    private function hasValidCoordinates(Complaint $complaint): bool
    {
        return is_numeric($complaint->latitude)
            && is_numeric($complaint->longitude)
            && (float) $complaint->latitude >= -90
            && (float) $complaint->latitude <= 90
            && (float) $complaint->longitude >= -180
            && (float) $complaint->longitude <= 180;
    }
}
