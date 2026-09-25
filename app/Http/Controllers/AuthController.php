<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use Illuminate\Support\Facades\Password as PasswordFacade;

class AuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    //  REGISTER
    // ══════════════════════════════════════════════════════════════════════════

    public function showRegister(): View
    {
        return view("auth.register");
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate(
            [
                "first_name" => [
                    "required",
                    "string",
                    "max:100",
                    'regex:/^[\pL\s\-\.]+$/u',
                ],
                "last_name" => [
                    "required",
                    "string",
                    "max:100",
                    'regex:/^[\pL\s\-\.]+$/u',
                ],
                "email" => [
                    "required",
                    "string",
                    "email:rfc,dns",
                    "max:255",
                    "unique:users,email",
                ],

                // Register form sends 'phone' — maps to contact_number column.
                // PH mobile format: 11 digits starting with 09 e.g. 09171234567
                "phone" => ["nullable", "string", 'regex:/^09\d{9}$/'],

                "barangay" => [
                    "nullable",
                    "string",
                    "in:" . implode(",", User::BARANGAYS),
                ],

                "password" => [
                    "required",
                    "confirmed",
                    Password::min(8)->mixedCase()->numbers(),
                ],

                "terms" => ["accepted"],
            ],
            [
                "first_name.regex" =>
                    "First name may only contain letters, spaces, hyphens, and dots.",
                "last_name.regex" =>
                    "Last name may only contain letters, spaces, hyphens, and dots.",
                "email.unique" => "This email address is already registered.",
                "phone.regex" =>
                    "Enter a valid 11-digit PH mobile number starting with 09 (e.g. 09171234567).",
                "barangay.in" => "The selected barangay is not valid.",
                "password.confirmed" => "Password confirmation does not match.",
                "terms.accepted" =>
                    "You must agree to the Terms of Service and Privacy Policy.",
            ],
        );

        $user = User::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "email" => $request->email,
            "contact_number" => $request->phone, // form sends 'phone', column is 'contact_number'
            "barangay" => $request->barangay,
            "password" => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()
            ->route("dashboard")
            ->with(
                "success",
                "Welcome to Daet Listens, " . $user->first_name . "!",
            );
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LOGIN
    // ══════════════════════════════════════════════════════════════════════════

    public function showLogin(): View
    {
        return view("auth.login");
    }

    public function login(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $request->validate([
            "email" => ["required", "string", "email"],
            "password" => ["required", "string"],
        ]);

        // Rate limit: 5 attempts per 60s per email+IP
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                "email" => trans("auth.throttle", [
                    "seconds" => $seconds,
                    "minutes" => ceil($seconds / 60),
                ]),
            ]);
        }

        if (
            !Auth::attempt(
                $request->only("email", "password"),
                $request->boolean("remember"),
            )
        ) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                "email" => __("auth.failed"),
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

        return redirect()->intended(route("dashboard"));
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

        return redirect()->route("login");
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PASSWORD RESET
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Show the forgot-password form.
     */
    public function showResetPassword(): View
    {
        return view("auth.forgot-password");
    }

    /**
     * Show the reset-password form.
     */
    public function showResetPasswordForm(Request $request): View
    {
        return view("auth.reset-password", ["token" => $request->route("token")]);
    }

    /**
     * Send password reset link to user's email using Laravel's Password facade.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $status = PasswordFacade::sendResetLink(
            $request->only('email')
        );

        return $status === PasswordFacade::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
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

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input("email")) . "|" . $request->ip(),
        );
    }
}
