<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Administrator TOTP enrollment.
 *
 * The QR code is only shown after the current password is re-entered, so a
 * stolen session cannot silently enroll another device and lock the owner out.
 */
class TwoFactorSetupController extends Controller
{
    public function show(Request $request, TwoFactorAuthentication $twoFactor): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        return view('settings.two-factor', array_merge(
            $twoFactor->dialogData($user),
            ['open' => false],
        ));
    }

    public function start(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => __('settings.security.current_incorrect'),
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => $twoFactor->generateSecret(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used_step' => null,
        ])->save();

        $request->session()->put('2fa.setup_authorized', true);

        $activityLogger->log(
            'auth.2fa.setup_started',
            "{$user->full_name} started two-factor authentication enrollment.",
            $user,
        );

        return redirect()->route('settings.two-factor.show');
    }

    public function enable(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show')
                ->with('error', __('settings.two_factor.already_enabled'));
        }

        if (! $request->session()->get('2fa.setup_authorized', false)) {
            return redirect()->route('settings.two-factor.show')
                ->with('error', __('settings.two_factor.setup_required'));
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $secret = $user->two_factor_secret;

        if (! is_string($secret) || $secret === '') {
            return redirect()->route('settings.two-factor.show')
                ->with('error', __('settings.two_factor.setup_expired'));
        }

        $recoveryCodes = $twoFactor->enable($user, $secret, $validated['code']);

        if ($recoveryCodes === null) {
            return back()->withErrors([
                'code' => __('settings.two_factor.invalid_code'),
            ]);
        }

        $request->session()->forget(['2fa.setup_authorized', '2fa.pending', '2fa.pending_until']);
        $request->session()->put('2fa.passed_at', now()->timestamp);

        $activityLogger->log(
            'auth.2fa.enabled',
            "{$user->full_name} enabled two-factor authentication.",
            $user,
        );

        return redirect()->route('settings.two-factor.show')
            ->with('status', __('settings.two_factor.enabled'))
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function recoveryCodes(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);
        abort_unless($user->hasTwoFactorEnabled(), 403);

        $validated = $request->validate([
            'recovery_code' => ['required', 'string', 'max:20'],
        ]);

        if (! $twoFactor->verify($user, $validated['recovery_code'])) {
            return back()->withErrors([
                'recovery_code' => __('settings.two_factor.invalid_code'),
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($recoveryCodes),
        ])->save();

        $activityLogger->log(
            'auth.2fa.recovery_codes_regenerated',
            "{$user->full_name} regenerated two-factor recovery codes.",
            $user,
        );

        return redirect()->route('settings.two-factor.show')
            ->with('status', __('settings.two_factor.recovery_codes_regenerated'))
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function reEnroll(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);
        abort_unless($user->hasTwoFactorEnabled(), 403);

        $validated = $request->validate([
            're_enroll_code' => ['required', 'string', 'max:20'],
        ]);

        if (! $twoFactor->verify($user, $validated['re_enroll_code'])) {
            return back()->withErrors([
                're_enroll_code' => __('settings.two_factor.invalid_code'),
            ]);
        }

        $twoFactor->reset($user);

        $user->forceFill([
            'two_factor_secret' => $twoFactor->generateSecret(),
        ])->save();

        $request->session()->forget('2fa.passed_at');
        $request->session()->put('2fa.setup_authorized', true);

        $activityLogger->log(
            'auth.2fa.re_enrollment_started',
            "{$user->full_name} started replacing their authenticator.",
            $user,
        );

        return redirect()->route('settings.two-factor.show')
            ->with('status', __('settings.two_factor.scan_new_code'));
    }

    public function disable(Request $request, TwoFactorAuthentication $twoFactor, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);
        abort_unless($user->hasTwoFactorEnabled(), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => __('settings.security.current_incorrect'),
            ]);
        }

        $twoFactor->reset($user);

        // The workspace will demand 2FA again if enforcement is on; the
        // current session must not inherit an old passed marker for an
        // account that no longer has 2FA enabled.
        $request->session()->forget([
            '2fa.passed_at',
            '2fa.setup_authorized',
            '2fa.pending',
            '2fa.pending_until',
            '2fa.failed',
        ]);

        $activityLogger->log(
            'auth.2fa.disabled',
            "{$user->full_name} disabled two-factor authentication.",
            $user,
        );

        return redirect()->route('settings', ['tab' => 'security'])
            ->with('status', __('settings.two_factor.disabled'));
    }
}
