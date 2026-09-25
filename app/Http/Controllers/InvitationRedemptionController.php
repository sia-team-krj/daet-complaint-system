<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationRedemptionController extends Controller
{
    public function show(string $code): RedirectResponse|View
    {
        $invitation = InvitationCode::with('department')
            ->where('code', $code)
            ->first();

        if (! $invitation || ! $invitation->isActive() || ! $invitation->department?->is_active) {
            return redirect()
                ->route('home')
                ->with('error', 'This invitation is no longer available. Ask your administrator for a new one.');
        }

        return view('invitations.redeem', compact('invitation'));
    }

    public function redeem(Request $request, string $code, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and dots.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and dots.',
            'contact_number.regex' => 'Enter a valid 11-digit PH mobile number starting with 09.',
            'terms.accepted' => 'You must accept the terms to create your staff account.',
        ]);

        $user = DB::transaction(function () use ($code, $validated, $activityLogger): User {
            $invitation = InvitationCode::where('code', $code)->lockForUpdate()->first();

            if (! $invitation || ! $invitation->isActive()) {
                throw ValidationException::withMessages([
                    'invitation' => 'This invitation is no longer available.',
                ]);
            }

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?? null,
                'password' => $validated['password'],
                'role' => 'staff',
                'department_id' => $invitation->department_id,
                'created_by' => $invitation->created_by,
                'is_active' => true,
            ]);

            $invitation->update([
                'used_count' => 1,
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
                'is_active' => false,
            ]);

            $departmentName = $invitation->department?->name ?? 'the assigned department';

            $activityLogger->log(
                'invitation.redeemed',
                "{$user->full_name} activated a staff account in {$departmentName}.",
                $invitation,
                departmentId: (int) $invitation->department_id,
                actor: $user,
            );

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('staff.dashboard')
            ->with('success', 'Your staff account is ready. Welcome to Daet Listens.');
    }
}
