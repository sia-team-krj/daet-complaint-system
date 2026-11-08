<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Store the language choice.
     *
     * Plain POST form works without JS (redirect back). Fetch/XHR callers
     * get JSON with the new locale so the UI can update without a reload:
     * the switcher JS updates <html lang>, reloads translations via a
     * soft navigation, and mirrors the choice to localStorage.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $supported = array_keys(config('localization.supported', []));

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($supported)],
        ]);

        $locale = $validated['locale'];

        $request->session()->put('locale', $locale);

        Cookie::queue(
            config('localization.cookie', 'locale'),
            $locale,
            config('localization.cookie_lifetime', 60 * 24 * 365),
        );

        if ($request->user() !== null) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        App::setLocale($locale);

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['locale' => $locale, 'lang' => $locale]);
        }

        return redirect()->back();
    }
}
