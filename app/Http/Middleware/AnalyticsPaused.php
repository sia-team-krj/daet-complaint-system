<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The analytics is parked.
 *
 * The pages, the service, the styles and the data are all still here — the
 * feature was set aside, not deleted. But nothing in the interface points at
 * it and these routes refuse, so it is out of sight while it waits.
 *
 * Taking the middleware off the two analytics route groups brings the whole
 * feature back.
 */
class AnalyticsPaused
{
    /**
     * Whether the feature is parked.
     *
     * The tests read this so the suite can skip the pages that are out of
     * sight, instead of failing them.
     */
    public const PAUSED = true;

    public function handle(Request $request, Closure $next)
    {
        abort(403, __('errors.analytics_paused'));
    }
}
