<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Require a verified email before allowing access to the application.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($this->shouldRememberIntended($request)) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()
            ->route('email.verify')
            ->with('error', 'Verify your email address to continue.');
    }

    /**
     * Only real page navigations are worth returning to after verification.
     *
     * The navbar bell polls /notifications/unread-count (and the feed) via
     * fetch on every page load, focus and pageshow (public/js/shell.js). That
     * poll runs while the account is still unverified, so storing its URL as
     * url.intended made `redirect()->intended()` land the user on that JSON
     * endpoint after they entered their code — a pretty-printed badge payload
     * instead of the dashboard.
     */
    private function shouldRememberIntended(Request $request): bool
    {
        // JSON/AJAX polls are never a page the user tried to visit.
        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        // fetch() polls do not navigate; browser address-bar clicks do.
        $fetchMode = $request->header('Sec-Fetch-Mode');
        if ($fetchMode !== null && $fetchMode !== '' && $fetchMode !== 'navigate') {
            return false;
        }

        if ($request->routeIs('notifications.unread-count', 'notifications.feed')) {
            return false;
        }

        if ($request->is('notifications/unread-count', 'notifications/feed')) {
            return false;
        }

        return true;
    }
}
