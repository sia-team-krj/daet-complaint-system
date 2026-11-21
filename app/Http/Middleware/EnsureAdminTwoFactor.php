<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps administrator routes behind TOTP when the feature is required.
 *
 * An admin without a confirmed secret is sent to enrollment. An enrolled admin
 * without a passed challenge in this session is sent to the challenge. The
 * setup and challenge routes themselves are outside the admin group.
 */
class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('admin.two_factor.required')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $next($request);
        }

        if (! $user->hasTwoFactorEnabled()) {
            if ($request->routeIs('settings.two-factor.*', 'logout')) {
                return $next($request);
            }

            return redirect()
                ->route('settings.two-factor.show')
                ->with('error', __('settings.two_factor.setup_required'));
        }

        if ($request->session()->has('2fa.passed_at')) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*', 'logout')) {
            return $next($request);
        }

        $expiresAt = $request->session()->get('2fa.pending_until');

        if ($expiresAt !== null && now()->timestamp > (int) $expiresAt) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('settings.two_factor.challenge_expired')]);
        }

        return redirect()
            ->route('two-factor.challenge')
            ->with('status', __('settings.two_factor.challenge_required'));
    }
}
