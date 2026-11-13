<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SoftNavigation;
use App\Models\Department;
use App\Services\DepartmentAnalyticsService;
use App\Support\AnalyticsPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The municipal analytics surface.
 *
 * `/admin/analytics` compares every active office with its own previous
 * period, alphabetically and without a score. `/admin/analytics/departments/{id}`
 * opens one office's full report, including the per-staff breakdown that never
 * reaches a staff session.
 */
class AdminAnalyticsController extends Controller
{
    use SoftNavigation;

    public function __construct(private DepartmentAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        return view('admin.analytics.index', $this->comparisonPayload($request));
    }

    public function browser(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wantsFragments($request)) {
            return redirect($this->fragmentFallbackUrl($request, 'admin.analytics.index'));
        }

        $payload = $this->comparisonPayload($request);

        return response()->json([
            'period' => view('analytics.partials.period', $payload)->render(),
            'content' => view('analytics.partials.comparison', $payload)->render(),
        ]);
    }

    public function print(Request $request): View
    {
        $payload = $this->comparisonPayload($request);

        return view('analytics.comparison-print', $payload);
    }

    public function department(Department $department, Request $request): View
    {
        return view('admin.analytics.department', $this->departmentPayload($department, $request));
    }

    public function departmentBrowser(Department $department, Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wantsFragments($request)) {
            return redirect($this->fragmentFallbackUrl(
                $request,
                'admin.analytics.department',
                ['department' => $department->id],
            ));
        }

        $payload = $this->departmentPayload($department, $request);

        return response()->json([
            'period' => view('analytics.partials.period', $payload)->render(),
            'content' => view('analytics.partials.content', $payload)->render(),
        ]);
    }

    public function departmentExport(Department $department, Request $request)
    {
        return $this->analytics->export($department, AnalyticsPeriod::fromRequest($request));
    }

    public function departmentPrint(Department $department, Request $request): View
    {
        $payload = $this->departmentPayload($department, $request);

        return view('analytics.print', array_merge($payload, [
            'surface' => 'admin',
            'staff_breakdown' => true,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function comparisonPayload(Request $request): array
    {
        $period = AnalyticsPeriod::fromRequest($request);

        return [
            'period' => $period,
            'data' => $this->analytics->comparison($period),
            'routeNames' => [
                'page' => 'admin.analytics.index',
                'department' => 'admin.analytics.department',
            ],
            'routeParams' => [],
            'fragmentUrl' => route('admin.analytics.browser'),
            'pagePath' => parse_url(route('admin.analytics.index'), PHP_URL_PATH),
            'printUrl' => route('admin.analytics.print', $period->query()),
            'surface' => 'admin',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentPayload(Department $department, Request $request): array
    {
        $period = AnalyticsPeriod::fromRequest($request);
        $routeParams = ['department' => $department->id];

        return [
            'department' => $department,
            'period' => $period,
            'data' => $this->analytics->department($department, $period, true),
            'routeNames' => [
                'page' => 'admin.analytics.department',
                'complaint' => 'admin.complaints.show',
                'comparison' => 'admin.analytics.index',
            ],
            'routeParams' => $routeParams,
            'fragmentUrl' => route('admin.analytics.department.browser', $routeParams),
            'pagePath' => parse_url(route('admin.analytics.department', $routeParams), PHP_URL_PATH),
            'exportUrl' => route('admin.analytics.department.export', array_merge($routeParams, $period->query())),
            'printUrl' => route('admin.analytics.department.print', array_merge($routeParams, $period->query())),
            'surface' => 'admin',
            'staffBreakdown' => true,
        ];
    }
}
