<?php

namespace App\Http\Controllers;

use App\Models\User;
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

class AuthController extends Controller
{
    private const BARANGAYS = [
        "Alawihao",
        "Awitan",
        "Bagasbas",
        "Barangay I (Hilahod)",
        "Barangay II (Pasig)",
        "Barangay III (Iraya)",
        "Barangay IV (Mantagbac)",
        "Barangay V (Pandan)",
        "Barangay VI (Centro)",
        "Barangay VII (Diego Liñan)",
        "Barangay VIII (Salcedo)",
        "Bibirao",
        "Borabod",
        "Calasgasan",
        "Camambugan",
        "Cobangbang",
        "Dogongan",
        "Gahonon",
        "Gubat (Moreno, Gubat, Mandulongan)",
        "Lag-on",
        "Magang",
        "Mambalite",
        "Mancruz",
        "Pamorangon",
        "San Isidro",
    ];

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
                "contact_number" => [
                    "nullable",
                    "string",
                    'regex:/^9\d{9}$/', // 10 digits, starts with 9
                ],
                "barangay" => [
                    "nullable",
                    "string",
                    "in:" . implode(",", self::BARANGAYS),
                ],
                "password" => [
                    "required",
                    "confirmed",
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                "terms" => ["accepted"],
            ],
            [
                "first_name.regex" =>
                    "First name may only contain letters, spaces, hyphens, and dots.",
                "last_name.regex" =>
                    "Last name may only contain letters, spaces, hyphens, and dots.",
                "email.unique" => "This email address is already registered.",
                "contact_number.regex" =>
                    "Enter a valid 10-digit mobile number starting with 9 (e.g. 9171234567).",
                "barangay.in" => "The selected barangay is not valid.",
                "password.confirmed" => "Password confirmation does not match.",
                "password.uncompromised" =>
                    "This password has appeared in a data breach. Please choose a different one.",
                "terms.accepted" =>
                    "You must agree to the Terms of Service and Privacy Policy.",
            ],
        );

        $user = User::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "email" => $request->email,
            "contact_number" => $request->contact_number,
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

    public function login(Request $request): RedirectResponse
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

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route("dashboard"));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ══════════════════════════════════════════════════════════════════════════

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route("login");
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
