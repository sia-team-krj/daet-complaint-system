<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        if (! config('admin.two_factor.required')) {
            return redirect()->route($user->homeRouteName());
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        if ($request->session()->has('2fa.passed_at')) {
            return redirect()->route($user->homeRouteName());
        }

        if ($this->challengeExpired($request)) {
            return $this->expireChallenge($request);
        }

        return view('auth.two-factor-challenge', [
            'recoveryAvailable' => count($user->two_factor_recovery_codes ?? []) > 0,
        ]);
    }

    public function verify(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        if ($this->challengeExpired($request)) {
            return $this->expireChallenge($request);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $key = '2fa-challenge:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('admin.two_factor.max_attempts', 5))) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'code' => __('settings.two_factor.too_many_attempts', ['seconds' => $seconds]),
            ]);
        }

        $code = trim($validated['code']);
        $isTotp = preg_match('/^\d{6}$/', $code) === 1;
        $verified = $isTotp
            ? $twoFactor->verify($user, $code)
            : $twoFactor->consumeRecoveryCode($user, $code);

        if (! $verified) {
            RateLimiter::hit($key, 60);

            $failures = (int) $request->session()->get('2fa.failed', 0) + 1;
            $request->session()->put('2fa.failed', $failures);

            $activityLogger->log(
                'auth.2fa.challenge_failed',
                "{$user->full_name} failed a two-factor challenge.",
                $user,
                metadata: ['method' => $isTotp ? 'totp' : 'recovery'],
            );

            if ($failures >= (int) config('admin.two_factor.max_attempts', 5)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => __('settings.two_factor.locked_out'),
                ]);
            }

            throw ValidationException::withMessages([
                'code' => __('settings.two_factor.invalid_code'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->forget(['2fa.failed', '2fa.pending', '2fa.pending_until']);
        $request->session()->put('2fa.passed_at', now()->timestamp);
        $request->session()->regenerate();

        if (! $isTotp) {
            $activityLogger->log(
                'auth.2fa.recovery_used',
                "{$user->full_name} used a two-factor recovery code.",
                $user,
                metadata: ['remaining' => $twoFactor->recoveryCodesRemaining($user)],
            );
        }

        $activityLogger->log(
            'auth.2fa.challenge_passed',
            "{$user->full_name} passed two-factor authentication.",
            $user,
        );

        return $this->redirectAfterChallenge($request, $user->homeRouteName());
    }

    private function challengeExpired(Request $request): bool
    {
        $expiresAt = $request->session()->get('2fa.pending_until');

        return $expiresAt !== null && now()->timestamp > (int) $expiresAt;
    }

    private function expireChallenge(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => __('settings.two_factor.challenge_expired'),
        ]);
    }

    private function redirectAfterChallenge(Request $request, string $fallbackRoute): RedirectResponse
    {
        // Never land on a notification poll endpoint if url.intended was
        // poisoned by a background fetch (same rule as the login redirect).
        $intended = $request->session()->pull('url.intended');
        $intendedPath = is_string($intended)
            ? (string) (parse_url($intended, PHP_URL_PATH) ?? '')
            : '';

        if (
            is_string($intended) && $intended !== ''
            && ! in_array($intendedPath, ['/notifications/unread-count', '/notifications/feed'], true)
        ) {
            return redirect()->to($intended);
        }

        return redirect()->route($fallbackRoute);
    }
}
