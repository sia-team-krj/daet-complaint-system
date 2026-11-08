<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
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
     * Precedence (per spec):
     *  - Signed-in user with a saved profile language: profile wins over
     *    whatever the guest was using before login.
     *  - Signed-in user with no saved language: keep the language already
     *    in use (session/cookie), else the system default (admin/staff
     *    requirement), else English.
     *  - Guest: session choice, then long-lived cookie (mirrors the
     *    localStorage choice set by the switcher JS), then browser
     *    Accept-Language, then system default, then English.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported', []));
        $systemDefault = SystemSetting::getValue('default_locale', config('localization.default', 'en'));
        if (!in_array($systemDefault, $supported, true)) {
            $systemDefault = 'en';
        }

        $user = $request->user();
        $savedLocale = $user?->locale;

        if ($user && in_array($savedLocale, $supported, true)) {
            $locale = $savedLocale;
        } elseif ($user) {
            // No saved choice: keep the language already in use
            // (session/cookie), otherwise the system default. The browser
            // Accept-Language header does not override the system default
            // for signed-in staff/admin.
            $locale = $request->session()->get('locale')
                ?? $request->cookie(config('localization.cookie', 'locale'))
                ?? $systemDefault;
        } else {
            $locale = $request->session()->get('locale')
                ?? $request->cookie(config('localization.cookie', 'locale'))
                ?? $request->getPreferredLanguage($supported)
                ?? $systemDefault;
        }

        if (!in_array($locale, $supported, true)) {
            $locale = $systemDefault;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
