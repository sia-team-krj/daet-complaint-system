<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show profile page for all user roles (citizen, staff, admin).
     */
    public function index(EmailVerificationService $verificationService): View
    {
        $user = Auth::user()->load('department');

        // Role-specific data
        $stats = match ($user->role) {
            'citizen' => $this->getCitizenStats($user),
            'staff' => $this->getStaffStats($user),
            'admin' => $this->getAdminStats(),
            default => [],
        };

        $pendingEmailChange = $verificationService
            ->pendingFor($user, EmailVerificationService::PURPOSE_EMAIL_CHANGE)
            ?->email;

        return view('profile.index', compact('user', 'stats', 'pendingEmailChange'));
    }

    private function getCitizenStats(User $user): array
    {
        return [
            'total_filed' => Complaint::where('user_id', $user->id)->count(),
            'resolved' => Complaint::where('user_id', $user->id)
                ->where('status', 'Resolved')
                ->count(),
            'pending' => Complaint::where('user_id', $user->id)
                ->whereNotIn('status', ['Resolved', 'Rejected', 'Closed'])
                ->count(),
        ];
    }

    private function getStaffStats(User $user): array
    {
        $handledCount = ComplaintLog::where('actor_id', $user->id)
            ->distinct('complaint_id')
            ->count('complaint_id');

        $resolvedThisMonth = ComplaintLog::where('actor_id', $user->id)
            ->where('new_status', 'Resolved')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Average days to resolve for complaints this staff worked on
        $avgDays = ComplaintLog::where('actor_id', $user->id)
            ->where('new_status', 'Resolved')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (complaints.created_at - complaint_logs.created_at)) / 86400) as avg_days')
            ->join('complaints', 'complaint_logs.complaint_id', '=', 'complaints.id')
            ->value('avg_days');

        return [
            'handled' => $handledCount,
            'resolved_this_month' => $resolvedThisMonth,
            'avg_days' => $avgDays ? round($avgDays, 1) : null,
        ];
    }

    private function getAdminStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_complaints' => Complaint::count(),
            'total_departments' => Department::count(),
        ];
    }

    /**
     * Update profile information. Email changes remain pending until verified.
     */
    public function update(
        Request $request,
        ActivityLogger $activityLogger,
        EmailVerificationService $verificationService,
    ): RedirectResponse {
        $user = Auth::user()->fresh();
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $newEmail = strtolower(trim($validated['email']));
        $emailChanged = $newEmail !== strtolower($user->email);

        if (
            $emailChanged
            && User::query()
                ->whereRaw('LOWER(email) = ?', [$newEmail])
                ->where('id', '!=', $user->id)
                ->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'That email address is already registered.']);
        }

        if (
            $emailChanged
            && EmailVerificationCode::query()
                ->whereRaw('LOWER(email) = ?', [$newEmail])
                ->where('user_id', '!=', $user->id)
                ->whereNull('consumed_at')
                ->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'That email address is already awaiting verification by another account.']);
        }

        if ($emailChanged) {
            $request->validate([
                'current_password' => ['required', 'string'],
            ]);

            if (! Hash::check($request->input('current_password'), $user->password)) {
                return back()
                    ->withInput()
                    ->withErrors(['current_password' => 'Your current password is incorrect.']);
            }
        }

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
        ]);

        if ($emailChanged) {
            try {
                $verificationService->issue(
                    $user,
                    $newEmail,
                    EmailVerificationService::PURPOSE_EMAIL_CHANGE,
                );
            } catch (Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('profile')
                    ->with('error', 'We could not send the verification email. Please try again.');
            }

            $request->session()->put(
                'email_verification_purpose',
                EmailVerificationService::PURPOSE_EMAIL_CHANGE,
            );

            $activityLogger->log(
                'profile.email_change_requested',
                "{$user->full_name} requested an email address change.",
                $user,
            );

            return redirect()
                ->route('email.verify')
                ->with('status', 'Enter the verification code sent to your new email address.');
        }

        $activityLogger->log(
            'profile.updated',
            "{$user->full_name} updated their profile information.",
            $user,
            metadata: ['updated_fields' => ['first_name', 'last_name']],
        );

        return redirect()->route('profile')
            ->with('status', 'Profile updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user()->fresh();

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('profile')
                ->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
            'must_change_password' => false,
            'password_expires_at' => null,
        ]);

        $activityLogger->log(
            'profile.password_changed',
            "{$user->full_name} changed their account password.",
            $user,
        );

        return redirect()->route('profile')
            ->with('status', 'Password updated successfully.');
    }
}
