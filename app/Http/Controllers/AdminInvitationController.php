<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Services\ActivityLogger;
use App\Services\StaffInvitationDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminInvitationController extends Controller
{
    /** Invitation codes are long strings, so the queue stays short per page. */
    private const PER_PAGE = 5;

    public function index(): View
    {
        // `created_at` alone is not a stable sort: invitations issued in the same
        // second tie, and an unstable order repeats or drops rows across pages.
        $invitations = InvitationCode::with(['department', 'createdBy', 'redeemedBy'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.invitations.index', compact('invitations', 'departments'));
    }

    /** Issue one invitation. */
    public function store(
        Request $request,
        ActivityLogger $activityLogger,
        StaffInvitationDelivery $delivery,
    ): RedirectResponse {
        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\s\-\.]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email',
            ],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, hyphens, and dots.',
            'email.unique' => 'That email already belongs to an account.',
        ]);

        $email = strtolower(trim($validated['email']));

        if (InvitationCode::query()
            ->where('invitee_email', $email)
            ->where('is_active', true)
            ->where('used_count', 0)
            ->where('expires_at', '>', now())
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'That email already has an active invitation.']);
        }

        $expiryHours = max(1, (int) config('invitations.expires_in_hours', 24));

        $invitation = DB::transaction(function () use ($validated, $email, $expiryHours, $activityLogger): InvitationCode {
            do {
                $code = Str::random(32);
            } while (InvitationCode::where('code', $code)->exists());

            $invitation = InvitationCode::create([
                'code' => $code,
                'invitee_name' => $validated['name'] ?? null,
                'invitee_email' => $email,
                'department_id' => $validated['department_id'],
                'created_by' => Auth::id(),
                'expires_at' => now()->addHours($expiryHours),
                'max_uses' => 1,
                'used_count' => 0,
                'is_active' => true,
            ]);

            $activityLogger->log(
                'invitation.generated',
                "Single-use staff invitation generated for {$invitation->department->name}, addressed to {$email}.",
                $invitation,
            );

            return $invitation;
        });

        try {
            $delivery->send($invitation, route('invitations.show', $invitation->code));
            $invitation->forceFill(['emailed_at' => now()])->save();

            return redirect()
                ->route('admin.invitations.index')
                ->with('status', "Invitation emailed to {$email}. The link expires in {$expiryHours} hours and can be used once.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.invitations.index')
                ->with('error', "The invitation was created but the email to {$email} could not be sent. Copy the link from the list and share it manually.");
        }
    }

    public function revoke(InvitationCode $invitation, ActivityLogger $activityLogger): RedirectResponse
    {
        if (! $invitation->is_active) {
            return back()->with('error', 'This invitation is no longer active.');
        }

        $invitation->update(['is_active' => false]);

        $departmentName = $invitation->department?->name ?? 'a department';

        $activityLogger->log(
            'invitation.revoked',
            "Staff invitation for {$departmentName} revoked.",
            $invitation,
        );

        return back()->with('status', 'Invitation revoked. It can no longer create an account.');
    }
}
