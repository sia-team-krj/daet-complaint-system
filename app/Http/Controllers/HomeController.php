<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Services\BarangayLocator;
use App\Services\TransparencyFeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * The public landing page.
     *
     * Authenticated users never see it: they are forwarded to the workspace
     * for their role, and the public counts are not threaded into a personal
     * dashboard.
     */
    public function index(TransparencyFeed $feed, BarangayLocator $barangays)
    {
        // Authenticated users always enter the workspace for their role.
        // This prevents the public home controller from showing system-wide
        // complaint totals inside a personal dashboard.
        if (auth()->check()) {
            $route = match (auth()->user()->role) {
                'admin' => 'admin.dashboard',
                'staff' => 'staff.dashboard',
                default => 'dashboard',
            };

            return redirect()->route($route);
        }

        // The proof strip and the recent-reports list are derived from the
        // same public payload the transparency register uses, so the two
        // pages can never disagree about what is public or how it counts.
        $payload = $feed->indexPayload($barangays);
        $publicStats = $payload['stats'];
        $reports = $payload['reports'];
        $allReports = $payload['allReports'] ?? $reports;

        $totalPublic = (int) $publicStats['total'];
        $inProgress = (int) $publicStats['in_progress'];
        $resolved = (int) $publicStats['resolved'];
        $avgDays = $resolved >= 5 ? $this->getAveragePublicResolutionDays() : null;

        return view('pages.home.guest', [
            'stats' => [
                'total' => $totalPublic,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'resolution_rate' => $publicStats['resolution_rate'],
                'offices' => Department::where('is_active', true)->count(),
                'avgDays' => $avgDays,
            ],
            'statusBreakdown' => $this->statusBreakdown($allReports),
            'recentReports' => $this->recentPublicReports($reports),
            'feed' => $payload,
        ]);
    }

    /**
     * Counts of each public status, in the fixed order the stacked bar and
     * its legend render. Only computed when the bar will be shown
     * (>= 5 public reports).
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $reports
     * @return array<int, array{key: string, label: string, count: int, share: int}>
     */
    private function statusBreakdown($reports): array
    {
        $total = $reports->count();

        if ($total < 5) {
            return [];
        }

        $order = ['submitted', 'under_review', 'in_progress', 'resolved', 'rejected', 'closed'];

        $counts = $reports->groupBy('status_key')->map->count();

        return collect($order)
            ->map(function (string $key) use ($counts, $total, $reports): ?array {
                $count = (int) ($counts[$key] ?? 0);

                if ($count === 0) {
                    return null;
                }

                $label = $reports->firstWhere('status_key', $key)['status_label'] ?? $key;

                return [
                    'key' => $key,
                    'label' => $label,
                    'count' => $count,
                    'share' => (int) round($count / $total * 100),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The three most recent public reports, shaped for the compact rows.
     * Titles are clamped to 70 characters at a word boundary; a title too
     * long to be a title (> 120 chars) is replaced by the category label.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $reports
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function recentPublicReports($reports)
    {
        return $reports
            ->take(3)
            ->map(function (array $report): array {
                $title = (string) $report['title'];
                $categoryLabel = (string) $report['category_label'];

                if (mb_strlen($title) > 120 || trim($title) === '') {
                    $title = $categoryLabel;
                } else {
                    $title = $this->clampAtWordBoundary($title, 70);
                }

                $report['title'] = $title;
                $report['relative_date'] = $this->relativeDate($report['iso_date'] ?? null);

                return $report;
            })
            ->values();
    }

    private function clampAtWordBoundary(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace >= 20) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B,–—-").'…';
    }

    private function relativeDate(?string $isoDate): ?string
    {
        if ($isoDate === null || trim($isoDate) === '') {
            return null;
        }

        try {
            return Carbon::parse($isoDate)->locale(app()->getLocale())->diffForHumans(short: true, syntax: Carbon::DIFF_ABSOLUTE);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Average days from submission to resolution, over public resolved
     * complaints only. Only consulted when at least 5 public complaints are
     * resolved; otherwise the page shows nothing in this slot.
     */
    private function getAveragePublicResolutionDays(): ?int
    {
        $avg = Complaint::public()
            ->where('status', ComplaintStatus::Resolved->value)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->selectRaw(
                'AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days',
            )
            ->value('avg_days');

        return $avg !== null ? (int) round((float) $avg) : null;
    }
}
