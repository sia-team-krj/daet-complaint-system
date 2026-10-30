<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\ActivityLogger;
use App\Services\BrevoPasswordResetSender;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    //  REGISTER
    // ══════════════════════════════════════════════════════════════════════════

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(
        Request $request,
        EmailVerificationService $verificationService,
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate(
            [
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[\pL\s\-\.]+$/u',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[\pL\s\-\.]+$/u',
                ],
                'email' => [
                    'required',
                    'string',
                    'email:rfc,dns',
                    'max:255',
                    'unique:users,email',
                ],

                // Register form sends 'phone' — maps to contact_number column.
                // PH mobile format: 11 digits starting with 09 e.g. 09171234567
                'phone' => ['nullable', 'string', 'regex:/^09\d{9}$/'],

                'barangay' => [
                    'nullable',
                    'string',
                    Rule::in(User::BARANGAYS),
                ],

                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers(),
                ],

                'terms' => ['accepted'],
            ],
            [
                'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and dots.',
                'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and dots.',
                'email.unique' => 'This email address is already registered.',
                'phone.regex' => 'Enter a valid 11-digit PH mobile number starting with 09 (e.g. 09171234567).',
                'barangay.in' => 'The selected barangay is not valid.',
                'password.confirmed' => 'Password confirmation does not match.',
                'terms.accepted' => 'You must agree to the Terms and Conditions and the Privacy Policy.',
            ],
        );

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->phone, // form sends 'phone', column is 'contact_number'
            'barangay' => $request->barangay,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(
            'email_verification_purpose',
            EmailVerificationService::PURPOSE_REGISTRATION,
        );

        $verificationSent = false;

        try {
            $verificationService->issue(
                $user,
                $user->email,
                EmailVerificationService::PURPOSE_REGISTRATION,
            );
            $verificationSent = true;
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('email.verify')
            ->with(
                $verificationSent ? 'status' : 'error',
                $verificationSent
                    ? 'Your account was created. Enter the verification code we sent to your email.'
                    : 'Your account was created, but we could not send the verification email. Use the resend option to try again.',
            );
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LOGIN
    // ══════════════════════════════════════════════════════════════════════════

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rate limit: 5 attempts per 60s per email+IP
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (
            ! Auth::attempt(
                $request->only('email', 'password'),
                $request->boolean('remember'),
            )
        ) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Contact your administrator.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::user();
        if (in_array($user->role, ['staff', 'admin'], true)) {
            $activityLogger->log(
                'auth.login',
                "{$user->full_name} signed in.",
                $user,
            );
        }

        return redirect()->intended(route('dashboard'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ══════════════════════════════════════════════════════════════════════════

    public function logout(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();

        if ($user && in_array($user->role, ['staff', 'admin'], true)) {
            $activityLogger->log(
                'auth.logout',
                "{$user->full_name} signed out.",
                $user,
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PASSWORD RESET
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Show the forgot-password form.
     */
    public function showResetPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Show the reset-password form.
     */
    public function showResetPasswordForm(Request $request): View|RedirectResponse
    {
        $email = trim((string) $request->query('email', ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return redirect()
                ->route('password.request')
                ->withErrors([
                    'email' => 'This reset link is missing a valid email address. Request a new link below.',
                ]);
        }

        return view('auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $email,
        ]);
    }

    /**
     * Send password reset link to user's email using Laravel's Password facade.
     */
    public function sendResetLink(
        Request $request,
        BrevoPasswordResetSender $brevoPasswordResetSender,
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        $email = (string) $request->input('email');
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        try {
            $status = PasswordFacade::sendResetLink(
                ['email' => $user?->email ?? $email],
                function (User $resetUser, string $token) use ($brevoPasswordResetSender): void {
                    $url = route('password.reset', [
                        'token' => $token,
                        'email' => $resetUser->email,
                    ]);

                    if ($this->shouldUseBrevoForPasswordReset()) {
                        $brevoPasswordResetSender->send($resetUser, $url);
                    } else {
                        $resetUser->notify(new ResetPasswordNotification($url));
                    }
                },
            );
        } catch (Throwable $exception) {
            if ($user) {
                PasswordFacade::deleteToken($user);
            }

            report($exception);

            return back()->withErrors([
                'email' => 'We could not send the reset email right now. Please try again shortly.',
            ]);
        }

        if ($status === PasswordFacade::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Please wait before requesting another reset email.',
            ]);
        }

        // Do not reveal whether an address belongs to an account.
        return back()->with('status', 'If an account exists for that email, a password reset link is on its way.');
    }

    /**
     * Reset user's password using token via Laravel's Password facade.
     */
    public function resetPassword(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $status = PasswordFacade::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($activityLogger) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                if (in_array($user->role, ['staff', 'admin'], true)) {
                    $activityLogger->log(
                        'auth.password_reset',
                        "{$user->full_name} reset their account password.",
                        $user,
                    );
                }
            }
        );

        return $status === PasswordFacade::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function shouldUseBrevoForPasswordReset(): bool
    {
        return trim((string) config('services.brevo.api_key')) !== '';
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')).'|'.$request->ip(),
        );
    }
}
