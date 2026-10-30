<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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

    /**
     * Invitations issued to a specific address may only create an account for
     * that address. The locked field is a convenience; this check is the guard.
     */
    private function ensureEmailMatchesInvitee(Request $request, InvitationCode $invitation): void
    {
        if (! $invitation->isReservedToEmail()) {
            return;
        }

        if (strtolower(trim((string) $request->input('email'))) !== $invitation->invitee_email) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was issued to a different email address.',
            ]);
        }
    }

    public function redeem(
        Request $request,
        string $code,
        ActivityLogger $activityLogger,
        EmailVerificationService $verificationService,
    ): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

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

        // Re-read the invitation before validation so a reserved address is
        // enforced, then re-check under a row lock inside the transaction.
        $reservedInvitation = InvitationCode::where('code', $code)->first();
        $this->ensureEmailMatchesInvitee($request, $reservedInvitation ?? new InvitationCode);

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
                    ? 'Your staff account was created. Enter the verification code we sent to your email.'
                    : 'Your staff account was created, but we could not send the verification email. Use the resend option to try again.',
            );
    }
}
