<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InvitationCode;
use App\Services\ActivityLogger;
use App\Services\InvitationListParser;
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
    private const PREVIEW_SESSION_KEY = 'admin.invitations.preview';

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

        $preview = session(self::PREVIEW_SESSION_KEY);

        return view('admin.invitations.index', compact('invitations', 'departments', 'preview'));
    }

    /**
     * Parse a pasted recipient list and hold it for review. Nothing is created
     * and no mail is sent until the admin confirms.
     */
    public function preview(Request $request, InvitationListParser $parser): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string', 'max:5000'],
            'default_department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
        ], [
            'recipients.required' => 'Paste at least one email address to check.',
        ]);

        $defaultDepartment = $validated['default_department_id'] !== null
            ? Department::find($validated['default_department_id'])
            : null;

        $result = $parser->parse($validated['recipients'], $defaultDepartment);

        if ($result['total'] === 0) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'No email addresses were found in that list.']);
        }

        if ($result['ready'] === 0) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'None of those lines can be invited yet. Check the reasons listed below.']);
        }

        $request->session()->put(self::PREVIEW_SESSION_KEY, [
            'rows' => $result['rows']->values()->all(),
            'ready' => $result['ready'],
            'skipped' => $result['skipped'],
            'invalid' => $result['invalid'],
            'recipients' => $validated['recipients'],
            'default_department_id' => $validated['default_department_id'],
        ]);

        return redirect()
            ->route('admin.invitations.index')
            ->with('status', "Checked {$result['total']} line(s): {$result['ready']} ready to send.");
    }

    /**
     * Issue one invitation. The bulk flow lives in preview() and send() and has
     * its own panel, so the two never share a form or a session key.
     */
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

    public function send(
        Request $request,
        ActivityLogger $activityLogger,
        StaffInvitationDelivery $delivery,
    ): RedirectResponse {
        $preview = $request->session()->pull(self::PREVIEW_SESSION_KEY);

        if (! is_array($preview) || ($preview['ready'] ?? 0) === 0) {
            return redirect()
                ->route('admin.invitations.index')
                ->with('error', 'That list is no longer available. Paste it again to continue.');
        }

        $expiryHours = max(1, (int) config('invitations.expires_in_hours', 24));
        $readyRows = collect($preview['rows'])->where('status', InvitationListParser::READY);

        $sent = 0;
        $failed = [];

        foreach ($readyRows as $row) {
            $invitation = DB::transaction(function () use ($row, $expiryHours, $activityLogger): InvitationCode {
                do {
                    $code = Str::random(32);
                } while (InvitationCode::where('code', $code)->exists());

                $invitation = InvitationCode::create([
                    'code' => $code,
                    'invitee_name' => $row['name'] ?: null,
                    'invitee_email' => $row['email'],
                    'department_id' => $row['department_id'],
                    'created_by' => Auth::id(),
                    'expires_at' => now()->addHours($expiryHours),
                    'max_uses' => 1,
                    'used_count' => 0,
                    'is_active' => true,
                ]);

                $activityLogger->log(
                    'invitation.generated',
                    "Single-use staff invitation generated for {$invitation->department->name}, addressed to {$row['email']}.",
                    $invitation,
                );

                return $invitation;
            });

            try {
                $delivery->send($invitation, route('invitations.show', $invitation->code));
                $invitation->forceFill(['emailed_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed[] = $row['email'];
            }
        }

        $skippedCount = (int) ($preview['skipped'] ?? 0) + (int) ($preview['invalid'] ?? 0);
        $skippedNote = $skippedCount > 0 ? " {$skippedCount} line(s) were skipped." : '';

        $activityLogger->log(
            'invitation.batch_issued',
            "Bulk invitation run: {$sent} sent, ".count($failed)." failed, {$skippedCount} skipped.",
        );

        if ($failed !== []) {
            return redirect()
                ->route('admin.invitations.index')
                ->with('error', "Sent {$sent} invitation(s). These could not be emailed and are in the list for you to copy manually: ".implode(', ', $failed).$skippedNote);
        }

        return redirect()
            ->route('admin.invitations.index')
            ->with('status', "Sent {$sent} invitation(s). Each link expires in {$expiryHours} hours and works once.".$skippedNote);
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
