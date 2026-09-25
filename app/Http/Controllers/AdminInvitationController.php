<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminInvitationController extends Controller
{
    public function index(): View
    {
        $invitations = InvitationCode::with(['department', 'createdBy', 'redeemedBy'])
            ->latest()
            ->paginate(15);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.invitations.index', compact('invitations', 'departments'));
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
        ]);

        $invitation = DB::transaction(function () use ($validated, $activityLogger): InvitationCode {
            do {
                $code = Str::random(32);
            } while (InvitationCode::where('code', $code)->exists());

            $invitation = InvitationCode::create([
                'code' => $code,
                'department_id' => $validated['department_id'],
                'created_by' => Auth::id(),
                'expires_at' => now()->addMinutes(10),
                'max_uses' => 1,
                'used_count' => 0,
                'is_active' => true,
            ]);

            $activityLogger->log(
                'invitation.generated',
                "Single-use staff invitation generated for {$invitation->department->name}.",
                $invitation,
            );

            return $invitation;
        });

        return redirect()
            ->route('admin.invitations.index')
            ->with('status', 'Invitation created. It expires in 10 minutes and can be used once.');
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
