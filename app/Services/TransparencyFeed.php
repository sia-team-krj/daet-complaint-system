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
     * How many of the most recent public complaints the register renders as rows.
     *
     * The register filters and sorts in the browser (see register-script), which
     * stays cheap and instant up to roughly this many rows. Beyond it the DOM
     * and the per-poll morph become the bottleneck, and filters would silently
     * operate on a partial view. So we render only the most recent N here and
     * keep the full aggregation (map, top areas, stats) over every public
     * complaint, which is bounded by barangay and therefore cheap.
     *
     * Above this threshold the next step is server-side pagination + filters
     * (query string + Livewire), not a larger cap. See docs/transparency-redesign.md.
     */
    public const REGISTER_ROW_CAP = 200;

    /**
     * Everything the public register renders, from one read of the database.
     *
     * @return array{reports: Collection, mapPoints: Collection, mapCenter: array, departmentOptions: Collection, categoryOptions: Collection, statusOptions: Collection, lastUpdated: string|null, stats: array{total: int, in_progress: int, resolved: int, resolution_rate: float|null}, topAreas: Collection}
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

        $allReports = $publicComplaints
            ->map(fn (Complaint $complaint): array => $this->publicReport($complaint, $barangays))
            ->values();

        // Summary page shows only the 3 newest public complaints.
        $reports = $allReports->take(3)->values();

        $inProgress = $publicComplaints->where('status', ComplaintStatus::InProgress->value)->count();
        $resolved = $publicComplaints->where('status', ComplaintStatus::Resolved->value)->count();
        $total = $publicComplaints->count();

        $stats = [
            'total' => $total,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'resolution_rate' => $total > 0 ? $resolved / $total : null,
        ];

        // The map and the table must agree on what counts as an area, so the
        // top-areas list is derived from the same grouped points the map uses,
        // not from a second aggregation of its own.
        $mapPoints = $this->mapPoints($geotaggedComplaints, $barangays);

        $lastUpdated = $publicComplaints->max('created_at');

        return [
            'reports' => $reports,
            'allReports' => $allReports,
            'totalPublic' => $publicComplaints->count(),
            'mapPoints' => $mapPoints,
            'mapCenter' => self::DAET_CENTER,
            'departmentOptions' => $allReports->pluck('department')->filter()->unique()->sort()->values(),
            'categoryOptions' => $this->categoryOptions($allReports),
            'statusOptions' => $this->statusOptions($allReports),
            'lastUpdated' => $lastUpdated !== null ? $this->publicDate($lastUpdated) : null,
            'stats' => $stats,
            'topAreas' => $this->topAreas($mapPoints),
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
                $barangay = $this->resolveBarangayKey($complaint, $barangays);

                return $barangay === null
                    ? null
                    : ['barangay' => $barangay, 'count' => 1];
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
     * Hard two-line clamp for the public title: keep whole words only,
     * never cut mid-word, and ellipsize at a word boundary.
     */
    private function clampTitle(string $title): string
    {
        if (mb_strlen($title) <= 90) {
            return $title;
        }

        $cut = mb_substr($title, 0, 90);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace >= 40) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B,-—–").'…';
    }

    /**
     * The ranked barangay list shown next to the map. Derived from the same
     * grouped points the map renders, so the two can never disagree. Areas
     * with fewer than three reports are deliberately absent: a bar for one
     * report would read as a finding, and one report is not a finding.
     */
    private function topAreas(Collection $mapPoints): Collection
    {
        $areas = $mapPoints
            ->filter(fn (array $point): bool => (int) $point['count'] >= 3)
            ->sortByDesc(fn (array $point): int => (int) $point['count'])
            ->take(5)
            ->values();

        $max = (int) ($areas->max('count') ?? 0);

        return $areas->map(fn (array $point): array => [
            'barangay' => $point['barangay'],
            'label' => $this->barangayLabel((string) $point['barangay']),
            'count' => (int) $point['count'],
            'share' => $max > 0 ? (int) round(((int) $point['count'] / $max) * 100) : 0,
        ])->values();
    }

    /**
     * One date formatter for the whole page ("M j, Y"), localized: English
     * renders "Nov 8, 2026", Filipino "Nob 8, 2026".
     */
    private function publicDate(\DateTimeInterface $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)
            ->locale(app()->getLocale())
            ->translatedFormat('M j, Y');
    }

    /**
     * Resolve a complaint to the canonical barangay key the reference table
     * knows. One answer, used by the map, the rows, and the top-areas list,
     * so they cannot drift apart.
     *
     * Precedence mirrors what publicLocation() is allowed to show: the
     * resident's own recorded barangay, then the address they wrote (matched
     * forgivingly — "VI" resolves to "Barangay VI (Centro)"), then the
     * account barangay, and only then a nearest-point guess, which is a
     * label of last resort and is still barangay-level, never a pin.
     *
     * @return string|null the canonical key, or null when nothing resolves
     */
    private function resolveBarangayKey(Complaint $complaint, BarangayLocator $barangays): ?string
    {
        $points = $barangays->points();
        $known = array_keys($points);

        $stated = trim((string) $complaint->barangay);
        if ($stated !== '') {
            if (isset($points[$stated])) {
                return $stated;
            }

            $match = $this->matchCanonicalBarangay($stated, $known);

            if ($match !== null) {
                return $match;
            }
        }

        $address = trim((string) $complaint->address_text);

        if (preg_match('/(?:barangay|brgy\.?)\s*([^,]+)/i', $address, $matches)) {
            $match = $this->matchCanonicalBarangay(trim($matches[1]), $known);

            if ($match !== null) {
                return $match;
            }
        }

        $account = trim((string) $complaint->user?->barangay);

        if ($account !== '') {
            if (isset($points[$account])) {
                return $account;
            }

            $stripped = preg_replace('/^barangay\s+/i', '', $account) ?? $account;
            $match = $this->matchCanonicalBarangay($stripped, $known);

            if ($match !== null) {
                return $match;
            }
        }

        // A guess from the coordinate. Only a barangay-level claim, and only
        // when nothing the resident wrote can be used.
        $located = trim((string) $barangays->locate($complaint->latitude, $complaint->longitude));

        return $located !== '' && isset($points[$located]) ? $located : null;
    }

    /**
     * Match free text against the canonical barangay keys, tolerating the
     * spellings residents actually write: "VI", "vi (centro)",
     * "BARANGAY VI", "Barangay Lag-on".
     *
     * @param  array<int, string>  $known
     */
    private function matchCanonicalBarangay(string $text, array $known): ?string
    {
        $needle = $this->normalizeBarangayName($text);

        if ($needle === '') {
            return null;
        }

        foreach ($known as $canonical) {
            if ($this->normalizeBarangayName($canonical) === $needle) {
                return $canonical;
            }
        }

        // "VI" -> "Barangay VI (Centro)": compare the leading part before
        // the parenthetical suffix, with a word boundary so "i" does not
        // match "Iraya".
        foreach ($known as $canonical) {
            $main = $this->normalizeBarangayName(explode('(', $canonical)[0]);

            if ($main === $needle || preg_match('/^'.preg_quote($needle, '/').'(\s|$)/u', $main) === 1) {
                return $canonical;
            }
        }

        return null;
    }

    private function normalizeBarangayName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/^barangay\s+/u', '', $normalized) ?? $normalized;
        $normalized = str_replace(['.', ','], ' ', $normalized);
        $normalized = preg_replace('/[()]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? $normalized;

        return $normalized;
    }

    /**
     * The canonical key with the word "Barangay" ensured for display, e.g.
     * "VI (Centro)" -> "Barangay VI (Centro)", "Lag-on" -> "Barangay Lag-on".
     */
    private function barangayLabel(string $canonical): string
    {
        $trimmed = trim($canonical);

        return preg_match('/^barangay\s+/i', $trimmed) === 1 ? $trimmed : "Barangay {$trimmed}";
    }

    /**
     * What the register may print as the area text for one row: the
     * resident's own words where there are any. No canonical key leaks,
     * because the key is a grouping artifact, not a name.
     */
    private function barangayDisplay(Complaint $complaint): ?string
    {
        $stated = trim((string) $complaint->barangay);

        if ($stated !== '') {
            return $stated;
        }

        $address = trim((string) $complaint->address_text);

        if (preg_match('/(?:barangay|brgy\.?)\s*([^,]+)/i', $address, $matches)) {
            return trim($matches[1]);
        }

        $account = trim((string) $complaint->user?->barangay);
        $stripped = preg_replace('/^barangay\s+/i', '', $account) ?? $account;

        return trim($stripped) !== '' ? trim($stripped) : null;
    }

    /**
     * Build a deliberately limited, public-safe representation of a report.
     */
    public function publicReport(Complaint $complaint, BarangayLocator $barangays): array
    {
        $status = $complaint->statusEnum;
        $category = $complaint->category;
        $alias = trim((string) $complaint->user?->display_alias);
        $method = $complaint->verificationMethodEnum();
        // The title column is what the resident typed; when it is missing the
        // row must say so out loud rather than borrowing the description.
        $rawTitle = trim((string) $complaint->publicTitle());
        $title = $rawTitle === ''
            ? __('transparency.v2.untitled')
            : $this->clampTitle($rawTitle);
        $barangayKey = $this->resolveBarangayKey($complaint, $barangays);
        $display = $this->barangayDisplay($complaint);

        return [
            'ticket_id' => $complaint->ticket_id,
            'url' => route('transparency.complaints.show', $complaint),
            'title' => $title,
            'raw_title' => $rawTitle,
            'search_text' => trim($complaint->ticket_id.' '.$rawTitle.' '.$this->categoryLabel($category).' '.($complaint->department?->name ?? 'Department pending').' '.($display !== null ? "Barangay {$display}" : 'Daet').($barangayKey !== null ? ' '.$barangayKey : '')),
            'category' => $category,
            'category_label' => $this->categoryLabel($category),
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_key' => Str::slug($status->value, '_'),
            'department' => $complaint->department?->name ?? __('transparency.v2.department_pending'),
            'department_code' => $complaint->department?->code,
            'location' => $this->publicLocation($complaint),
            'barangay' => $barangayKey ?? '',
            'barangay_label' => $display !== null ? "Barangay {$display}" : 'Daet',
            'reporter' => $alias !== '' ? $alias : __('transparency.v2.community_member'),
            'date' => $complaint->created_at !== null
                ? $this->publicDate($complaint->created_at)
                : __('transparency.v2.date_unavailable'),
            'iso_date' => $complaint->created_at?->toAtomString() ?? '',
            // Which of the five workflow segments is current. Rejected is
            // off-path, so it gets no step and the bar renders neutral.
            'progress_step' => match (Str::slug($status->value, '_')) {
                'submitted' => 1,
                'under_review', 'verified', 'assigned' => 2,
                'in_progress' => 3,
                'resolved' => 4,
                'closed' => 5,
                default => null,
            },
            // Whether the department actually settled verification — the page
            // shows a "Verified" pill only when this is true.
            'verified' => $method !== null,
            // How the department settled it, published rather than a single vague
            // "verified". "Report reviewed" and "Site attended" are different
            // claims and a resident is entitled to know which one happened.
            'verification' => $method?->label() ?? __('transparency.v2.verification_unrecorded'),
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
     * thing here the resident was actually asked, then the address they
     * wrote (the problem's location), then the account value (where they
     * live, a weaker claim about where the problem is). The register shows
     * the resident's own words; resolveBarangayKey() is the same precedence
     * matched onto the canonical reference table for the map, and the two
     * never publish anything more precise than a barangay.
     */
    public function publicLocation(Complaint $complaint): string
    {
        $display = $this->barangayDisplay($complaint);

        if ($display === null) {
            return 'Daet, Camarines Norte';
        }

        $display = preg_replace('/^Barangay\s+/i', '', trim($display)) ?? $display;

        if (strcasecmp(trim($display), 'Daet') === 0) {
            return 'Daet, Camarines Norte';
        }

        return "Barangay {$display}, Daet";
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