<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return redirect()
                ->route("dashboard")
                ->with("error", "Access denied. Admins only.");
        }

        return $next($request);
    }
}
