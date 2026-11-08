<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.change-password');
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ], [
            'new_password.confirmed' => __('auth_ui.confirmed'),
        ]);

        $user = Auth::user();
        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
            'must_change_password' => false,
            'password_expires_at' => null,
        ])->save();

        $activityLogger->log(
            'auth.password_changed_forced',
            "{$user->full_name} set a new password after signing in with a temporary password.",
            $user,
        );

        $request->session()->regenerate();

        return redirect()
            ->route($user->email_verified_at ? 'dashboard' : 'email.verify')
            ->with('status', 'Your password has been updated.');
    }
}
