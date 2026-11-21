<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates new complaints on resident identity verification.
 *
 * Only new complaints and POST submissions are gated; existing reports and
 * other self-service flows remain available.
 */
class EnsureResidentIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('identity.verification_required')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->isCitizen()) {
            return $next($request);
        }

        if ($user->canFileComplaints()) {
            return $next($request);
        }

        return redirect()->route('identity.verify.show')
            ->with('error', __('identity.errors.requires_verification'));
    }
}
