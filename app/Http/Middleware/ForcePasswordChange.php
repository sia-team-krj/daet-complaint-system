<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Staff created with a temporary password must set their own password
     * before they can use the portal. Everyone else passes through.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowed = [
                'password.change.show',
                'password.change.store',
                'logout',
                'profile.password',
            ];

            if (! $request->routeIs($allowed)) {
                return redirect()
                    ->route('password.change.show')
                    ->with('status', 'You must set a new password before continuing.');
            }
        }

        return $next($request);
    }
}
