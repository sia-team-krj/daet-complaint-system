<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the active locale before the request reaches a controller.
     *
     * Priority:
     *   1. The value chosen in this session (the language switcher).
     *   2. The long-lived guest cookie (the choice survives logout).
     *   3. The signed-in user's saved preference.
     *   4. The browser's Accept-Language header.
     *   5. The application default.
     *
     * Only values in config/localization.php are accepted, so a forged
     * session or cookie cannot make the app load an arbitrary locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported', []));
        $fallback = config('localization.default', config('app.locale', 'en'));

        $locale = $request->session()->get('locale')
            ?? $request->cookie(config('localization.cookie', 'locale'))
            ?? $request->user()?->locale
            ?? $request->getPreferredLanguage($supported)
            ?? $fallback;

        if (! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
