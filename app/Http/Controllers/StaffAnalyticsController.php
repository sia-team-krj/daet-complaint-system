<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SoftNavigation;
use App\Services\DepartmentAnalyticsService;
use App\Support\AnalyticsPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * A department's own analytics.
 *
 * The office is decided by the signed-in staff member, never by the URL: a
 * staff account may only read the numbers for the department it belongs to.
 * Administrators who want another office use the admin surface, where the
 * department is an explicit, authorised route parameter.
 */
class StaffAnalyticsController extends Controller
{
    use SoftNavigation;

    public function __construct(private DepartmentAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $payload = $this->payload($request);

        return view('staff.analytics.index', $payload);
    }

    public function browser(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wantsFragments($request)) {
            return redirect($this->fragmentFallbackUrl($request, 'staff.analytics.index'));
        }

        $payload = $this->payload($request);

        return response()->json([
            'period' => view('analytics.partials.period', $payload)->render(),
            'content' => view('analytics.partials.content', $payload)->render(),
        ]);
    }

    public function export(Request $request)
    {
        $department = $this->department();
        $period = AnalyticsPeriod::fromRequest($request);

        return $this->analytics->export($department, $period);
    }

    public function print(Request $request): View
    {
        $payload = $this->payload($request);

        return view('analytics.print', array_merge($payload, [
            'surface' => 'staff',
            'staff_breakdown' => false,
        ]));
    }

    /**
     * Everything the page, its fragments and its print view need.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $department = $this->department();
        $period = AnalyticsPeriod::fromRequest($request);

        $routeNames = [
            'page' => 'staff.analytics.index',
            'complaint' => 'staff.complaints.show',
        ];

        return [
            'department' => $department,
            'period' => $period,
            'data' => $this->analytics->department($department, $period),
            'routeNames' => $routeNames,
            'routeParams' => [],
            'fragmentUrl' => route('staff.analytics.browser'),
            'pagePath' => parse_url(route('staff.analytics.index'), PHP_URL_PATH),
            'exportUrl' => route('staff.analytics.export', $period->query()),
            'printUrl' => route('staff.analytics.print', $period->query()),
            'surface' => 'staff',
            'staffBreakdown' => false,
        ];
    }

    private function department(): \App\Models\Department
    {
        $department = Auth::user()?->department;

        abort_unless($department !== null, 403);

        return $department;
    }
}
