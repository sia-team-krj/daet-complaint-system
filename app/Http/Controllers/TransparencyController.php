<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Services\DepartmentRouter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $categoryLabels = $this->categoryLabels();
        $category = $complaint->category;
        $alias = trim((string) $complaint->user?->display_alias);

        return [
            'ticket_id' => $complaint->ticket_id,
            'title' => $complaint->title,
            'category' => $category,
            'category_label' => $categoryLabels->get(
                $category,
                Str::headline(str_replace('_', ' ', $category)),
            ),
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
