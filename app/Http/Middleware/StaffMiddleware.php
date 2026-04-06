<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     * Ensure user is staff and has a department assigned.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Must be authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Must be staff or admin (admin can access staff panel too)
        if (!in_array($user->role, ['staff', 'admin'])) {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. Staff only.');
        }

        // Staff must have department assigned
        if ($user->role === 'staff' && !$user->department_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Your account is not assigned to any department. Please contact admin.');
        }

        return $next($request);
    }
}
