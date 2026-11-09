<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verificationService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $purpose = $this->resolvePurpose($request, $user);
        $pending = $this->verificationService->pendingFor($user, $purpose);

        if ($user->hasVerifiedEmail() && ! $pending) {
            return redirect()->route($this->destinationRoute($user, $purpose));
        }

        return view('auth.verify-email', [
            'user' => $user,
            'purpose' => $purpose,
            'pending' => $pending,
            'verificationEmail' => $pending?->email ?? $user->email,
            'isEmailChange' => $purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE,
            'resendAvailableIn' => $this->resendAvailableIn($user, $purpose),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'purpose' => [
                'required',
                Rule::in([
                    EmailVerificationService::PURPOSE_REGISTRATION,
                    EmailVerificationService::PURPOSE_EMAIL_CHANGE,
                ]),
            ],
        ]);

        $user = $request->user();
        $purpose = $validated['purpose'];
        $verifyKey = $this->verifyRateLimitKey($user, $purpose);

        if (RateLimiter::tooManyAttempts($verifyKey, 10)) {
            return redirect()->route('email.verify', ['purpose' => $purpose])
                ->withInput(['purpose' => $purpose])
                ->withErrors(['code' => 'Too many attempts. Request a new verification code.']);
        }
        RateLimiter::hit($verifyKey, 60);

        $pending = $this->verificationService->pendingFor($user, $purpose);

        if (! $pending) {
            return redirect()->route('email.verify', ['purpose' => $purpose])
                ->withInput(['purpose' => $purpose])
                ->withErrors(['code' => 'Your verification session has expired. Request a new code.']);
        }

        if (
            $purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE
            && User::query()
                ->whereRaw('LOWER(email) = ?', [$pending->email])
                ->where('id', '!=', $user->id)
                ->exists()
        ) {
            return redirect()->route('email.verify', ['purpose' => $purpose])
                ->withInput(['purpose' => $purpose])
                ->withErrors(['email' => 'That email address is no longer available.']);
        }

        if (! $this->verificationService->verify(
            $user,
            $pending->email,
            $purpose,
            $validated['code'],
        )) {
            return redirect()->route('email.verify', ['purpose' => $purpose])
                ->withInput(['purpose' => $purpose])
                ->withErrors(['code' => 'The verification code is invalid or has expired.']);
        }

        if ($purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE) {
            $user->forceFill([
                'email' => $pending->email,
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user->markEmailAsVerified();
        }

        $this->activityLogger->log(
            $purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE
                ? 'profile.email_changed'
                : 'auth.email_verified',
            $purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE
                ? "{$user->full_name} verified and updated their email address."
                : "{$user->full_name} verified their email address.",
            $user,
        );

        RateLimiter::clear($verifyKey);
        $request->session()->forget('email_verification_purpose');

        if ($purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE) {
            return redirect()
                ->route('profile')
                ->with('status', __('auth.email_verified_and_updated'));
        }

        return redirect()
            ->to($this->safeDestination($request, $user, $purpose))
            ->with('status', __('auth.email_verified'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => [
                'required',
                Rule::in([
                    EmailVerificationService::PURPOSE_REGISTRATION,
                    EmailVerificationService::PURPOSE_EMAIL_CHANGE,
                ]),
            ],
        ]);

        $user = $request->user();
        $purpose = $validated['purpose'];
        $pending = $this->verificationService->pendingFor($user, $purpose);

        if ($purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE && ! $pending) {
            return redirect()
                ->route('email.verify', ['purpose' => $purpose])
                ->with('error', __('auth.start_email_change_before_resend'));
        }

        if ($user->hasVerifiedEmail() && ! $pending) {
            return redirect()->route($this->destinationRoute($user, $purpose));
        }

        $remaining = $this->resendAvailableIn($user, $purpose);

        if ($remaining > 0) {
            return redirect()
                ->route('email.verify', ['purpose' => $purpose])
                ->with(
                    'error',
                    __('auth.wait_for_resend', ['seconds' => $remaining]),
                );
        }

        $maxPerHour = max(1, (int) config('email-verification.resend_max_per_hour', 10));

        if (RateLimiter::tooManyAttempts($this->resendHourlyRateLimitKey($user, $purpose), $maxPerHour)) {
            return redirect()
                ->route('email.verify', ['purpose' => $purpose])
                ->with('error', __('auth.several_codes_requested'));
        }

        /*
         * Both counters are incremented before the send, not after.
         *
         * Checking then sending then recording leaves a window where a burst of
         * requests all pass the check before any of them records, so the limit
         * can be bypassed by simply asking several times at once. Recording
         * first makes the first request win and the rest queue behind the
         * cooldown.
         *
         * It also means a failed send still consumes the cooldown. That is
         * deliberate: when the mail transport is broken, the alternative is a
         * retry storm against a provider that is already failing.
         */
        RateLimiter::hit($this->resendRateLimitKey($user, $purpose), $this->resendCooldown());
        RateLimiter::hit($this->resendHourlyRateLimitKey($user, $purpose), 3600);

        $email = $pending?->email ?? $user->email;

        try {
            $this->verificationService->issue($user, $email, $purpose);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('email.verify', ['purpose' => $purpose])
                ->with('error', __('auth.could_not_send_verification_email'));
        }

        return redirect()
            ->route('email.verify', ['purpose' => $purpose])
            ->with('status', __('auth.new_verification_code_sent'));
    }

    private function resolvePurpose(Request $request, User $user): string
    {
        $purpose = $request->input('purpose')
            ?? $request->session()->get('email_verification_purpose');

        if (in_array($purpose, [
            EmailVerificationService::PURPOSE_REGISTRATION,
            EmailVerificationService::PURPOSE_EMAIL_CHANGE,
        ], true)) {
            if (! $user->hasVerifiedEmail()
                && $purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE) {
                return EmailVerificationService::PURPOSE_REGISTRATION;
            }

            return $purpose;
        }

        return $user->hasVerifiedEmail()
            ? EmailVerificationService::PURPOSE_EMAIL_CHANGE
            : EmailVerificationService::PURPOSE_REGISTRATION;
    }

    /**
     * Resolve where to send the user after verification.
     *
     * `url.intended` may already hold a notification poll URL
     * (/notifications/unread-count) stored by EnsureEmailIsVerified before
     * the guard was tightened. Blindly using `intended()` would dump the
     * user on that JSON endpoint, so polling/API paths — and anything
     * external — fall back to the role dashboard.
     */
    private function safeDestination(Request $request, User $user, string $purpose): string
    {
        $fallback = route($this->destinationRoute($user, $purpose));
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = (string) (parse_url($intended, PHP_URL_PATH) ?? '');

        if (in_array($path, ['/notifications/unread-count', '/notifications/feed'], true)) {
            return $fallback;
        }

        // Only accept internal URLs (absolute on this host, or a local path).
        $host = parse_url($intended, PHP_URL_HOST);
        if ($host !== null && $host !== $request->getHost()) {
            return $fallback;
        }

        if ($host === null && ! str_starts_with($path, '/')) {
            return $fallback;
        }

        return $intended;
    }

    private function destinationRoute(User $user, string $purpose): string
    {
        if ($purpose === EmailVerificationService::PURPOSE_EMAIL_CHANGE) {
            return 'profile';
        }

        return $user->homeRouteName();
    }

    private function verifyRateLimitKey(User $user, string $purpose): string
    {
        return "email-otp:verify:{$user->id}:{$purpose}";
    }

    private function resendRateLimitKey(User $user, string $purpose): string
    {
        return "email-otp:resend:{$user->id}:{$purpose}";
    }

    private function resendHourlyRateLimitKey(User $user, string $purpose): string
    {
        return "email-otp:resend-hourly:{$user->id}:{$purpose}";
    }

    private function resendCooldown(): int
    {
        return max(1, (int) config('email-verification.resend_cooldown_seconds', 60));
    }

    /**
     * Seconds until this account may request another code, 0 if it may now.
     *
     * The view renders this so the button can count down instead of letting
     * someone click into a rejection.
     */
    private function resendAvailableIn(User $user, string $purpose): int
    {
        return (int) ceil(
            RateLimiter::availableIn($this->resendRateLimitKey($user, $purpose)),
        );
    }
}
