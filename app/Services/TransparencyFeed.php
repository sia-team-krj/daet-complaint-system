<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds what the public transparency page is allowed to show.
 *
 * This used to live inside TransparencyController, which was fine while the
 * register was rendered once per page load. It is now shared by the controller
 * (which still serves a complaint's detail page) and by TransparencyRegister,
 * the component that re-renders the index on a timer — and that sharing is the
 * whole reason it was pulled out rather than copied.
 *
 * Copying it would have been the tempting shortcut, and it is worth being blunt
 * about why that is a bad idea here. This is the code that decides what a
 * resident's complaint looks like to the public: whether it appears at all,
 * which barangay it is attributed to, how strong its heat-map pin is. A second
 * copy of that logic is a second set of answers to "what may this town see",
 * and the two would drift apart silently, with the stale copy being the one
 * that ships. One copy, one place to change.
 */
class TransparencyFeed
{
    /**
     * The public map is intentionally approximate. Two decimal places keeps
     * the heat signal useful without exposing an exact report coordinate.
     */
    public const DAET_CENTER = [14.1122, 122.9553];

    /**
     * Everything the public register renders, from one read of the database.
     *
     * @return array{reports: Collection, mapPoints: Collection, mapCenter: array, departmentOptions: Collection, categoryOptions: Collection, statusOptions: Collection, lastUpdated: string}
     */
    public function indexPayload(BarangayLocator $barangays): array
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

        return [
            'reports' => $reports,
            'mapPoints' => $this->mapPoints($geotaggedComplaints, $barangays),
            'mapCenter' => self::DAET_CENTER,
            'departmentOptions' => $reports->pluck('department')->filter()->unique()->sort()->values(),
            'categoryOptions' => $this->categoryOptions($reports),
            'statusOptions' => $this->statusOptions($reports),
            'lastUpdated' => $lastUpdated?->format('M j, Y') ?? 'No public reports yet',
        ];
    }

    /**
     * Build the public heat map, aggregated to the barangay.
     *
     * This used to round each complaint's coordinate to two decimal places,
     * about 1.1 km, and plot that. Two decimal places sounds coarse but Daet
     * spans roughly 10 km, so a pin still narrowed a report down to a ninth of
     * the municipality — and for the smaller barangays, to the barangay itself
     * with an address attached. The page promised "approximate areas, not exact
     * addresses or individual location pins", and that promise was not true.
     *
     * So the pin is now the barangay's own reference point and the intensity
     * counts how many public reports fall in that barangay. A reader learns
     * which barangays have problems and roughly how many, which is the whole
     * point of a public transparency feed, and learns nothing about which road
     * a particular person's pothole is on.
     */
    private function mapPoints(Collection $complaints, BarangayLocator $barangays): Collection
    {
        return $complaints
            ->map(function (Complaint $complaint) use ($barangays): ?array {
                // The resident's own answer, and only a guess for a row filed
                // before the form asked. A guessed point on a public map is a
                // published claim about where somebody lives, made by a lookup
                // table with one point per barangay and no boundaries.
                $barangay = trim((string) $complaint->barangay);

                if ($barangay === '') {
                    $barangay = (string) $barangays->locate($complaint->latitude, $complaint->longitude);
                }

                if ($barangay === '') {
                    return null;
                }

                return ['barangay' => $barangay, 'count' => 1];
            })
            ->filter()
            ->groupBy('barangay')
            ->map(function (Collection $group, string $barangay) use ($barangays): ?array {
                $point = $barangays->points()[$barangay] ?? null;

                if ($point === null) {
                    return null;
                }

                $count = $group->count();

                return [
                    'barangay' => $barangay,
                    'lat' => $point[0],
                    'lng' => $point[1],
                    'intensity' => $count < 3 ? 0 : min(1, 0.4 + (min($count, 5) * 0.12)),
                    'count' => $count,
                    'low_volume' => $count < 3,
                ];
            })
            ->filter()
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
        $method = $complaint->verificationMethodEnum();

        return [
            'ticket_id' => $complaint->ticket_id,
            'url' => route('transparency.complaints.show', $complaint),
            'title' => $complaint->publicTitle(),
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
            // How the department settled it, published rather than a single vague
            // "verified". "Report reviewed" and "Site attended" are different
            // claims and a resident is entitled to know which one happened.
            'verification' => $method?->label() ?? 'Verification not recorded',
            'verification_key' => $method !== null ? Str::slug($method->value, '_') : 'unrecorded',
            'site_attended' => $method?->requiresSiteAttendance() ?? false,
        ];
    }

    public function categoryLabel(string $category): string
    {
        return $this->categoryLabels()->get(
            $category,
            Str::headline(str_replace('_', ' ', $category)),
        );
    }

    /**
     * The barangay shown publicly, in order of how much it can be trusted.
     *
     * The complaint's own recorded barangay comes first because it is the one
     * thing here the resident was actually asked. Everything below it is a
     * fallback, and each is worse: the address is free text we have to parse,
     * the account value is where they live rather than where the problem is,
     * and there is nothing after that.
     *
     * What is deliberately *not* here is a guess derived from the coordinate.
     * OpenStreetMap carries a single label point for each of Daet's barangays
     * and no boundary polygons at all, so nearest-point is wrong across most of
     * a large barangay. Publishing a wrong barangay is worse than publishing a
     * less specific one, because a reader has no way to know it is wrong.
     */
    public function publicLocation(Complaint $complaint): string
    {
        $stated = trim((string) $complaint->barangay);

        if ($stated !== '') {
            return "Barangay {$stated}, Daet";
        }

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
    public function categoryLabels(): Collection
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