<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Store the resident's language choice.
     *
     * The switch is a plain POST form so it works without JavaScript. The
     * choice is kept in the session for this visit, in a long-lived cookie
     * for guests, and on the user record once someone is signed in.
     */
    public function update(Request $request): RedirectResponse
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

        return redirect()->back();
    }
}
