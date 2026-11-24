<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The staff analytics is parked.
 *
 * The admin analytics is live. Only the staff-facing report is set aside: its
 * pages, service and styles are still here, but its routes refuse and the
 * staff sidebar does not point at it.
 *
 * Removing the middleware from the staff analytics route group and setting
 * STAFF_PAUSED to false brings it back.
 */
class AnalyticsPaused
{
    /**
     * Whether the staff-facing analytics is parked.
     *
     * The tests read this so the suite can skip the staff pages that are out
     * of sight, instead of failing them.
     */
    public const STAFF_PAUSED = true;

    public function handle(Request $request, Closure $next)
    {
        abort(403, __('errors.analytics_paused'));
    }
}
