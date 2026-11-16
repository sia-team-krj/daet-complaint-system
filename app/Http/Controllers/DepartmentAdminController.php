<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SoftNavigation;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use App\Services\ActivityLogger;
use App\Services\DepartmentAnalyticsService;
use App\Services\EmailVerificationService;
use App\Services\NotificationService;
use App\Services\StaffInvitationDelivery;
use App\Support\AnalyticsPeriod;
use App\Support\TemporaryPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * The Department Administrator's office surface.
 *
 * The role is deliberately narrower than a global administrator: it manages
 * the staff of one office, verifies its complaints, assigns its work, and
 * reads its audit trail. It never sees another office, never changes a
 * profile, never transfers anybody, and never appoints a peer. The global
 * administrator remains the fallback for everything outside that boundary.
 */
class DepartmentAdminController extends Controller
{
    use SoftNavigation;

    /** Invitations shown on the office page. */
    private const INVITATION_PAGE_SIZE = 10;

    /** Audit entries per page. */
    private const AUDIT_PAGE_SIZE = 25;

    public function __construct(private DepartmentAnalyticsService $analytics) {}

    public function index(): View
    {
        $office = $this->office();

        $members = User::query()
            ->where('department_id', $office->id)
            ->whereIn('role', ['staff', 'department_admin'])
            ->orderByRaw("CASE WHEN role = 'department_admin' THEN 0 ELSE 1 END")
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $invitations = InvitationCode::query()
            ->where('department_id', $office->id)
            ->where('is_active', true)
            ->where('used_count', 0)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->limit(self::INVITATION_PAGE_SIZE)
            ->get();

        $stats = [
            'members' => $members->count(),
            'active' => $members->where('is_active', true)->count(),
            'verifiers' => $members->filter(fn (User $member): bool => $member->canVerifyComplaints())->count(),
            'invitations' => $invitations->count(),
        ];

        return view('staff.office.index', compact('office', 'members', 'invitations', 'stats'));
    }

    /**
     * Create a staff account, either by invitation link or by emailed
     * temporary password. No password is ever chosen by the manager.
     */
    public function storeStaff(
        Request $request,
        ActivityLogger $activityLogger,
        EmailVerificationService $verificationService,
        StaffInvitationDelivery $invitationDelivery,
    ): RedirectResponse {
        $office = $this->office();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'creation_method' => $request->input('creation_method', 'emailed_temp_password'),
        ]);

        $method = (string) $request->input('creation_method');
        if (! in_array($method, ['invitation', 'emailed_temp_password'], true)) {
            $method = 'emailed_temp_password';
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'creation_method' => ['required', Rule::in(['invitation', 'emailed_temp_password'])],
        ];

        if ($method === 'invitation') {
            $rules['invitation_expires_in_hours'] = ['required', Rule::in([24, 48, 168])];
        }

        $validated = $request->validate($rules, [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and dots.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and dots.',
            'contact_number.regex' => 'Enter a valid 11-digit PH mobile number starting with 09.',
            'invitation_expires_in_hours.in' => 'Choose a valid invitation expiry.',
        ]);

        $email = strtolower(trim($validated['email']));
        $fullName = trim($validated['first_name'].' '.$validated['last_name']);

        if ($method === 'invitation') {
            return $this->issueInvitation(
                $office,
                $fullName,
                $email,
                (int) $validated['invitation_expires_in_hours'],
                $activityLogger,
                $invitationDelivery,
            );
        }

        $tempExpiryHours = max(1, (int) config('staff_accounts.temp_password_expires_in_hours', 72));
        $tempPassword = TemporaryPassword::generate(14);

        $staff = DB::transaction(function () use ($validated, $office, $email, $tempPassword, $tempExpiryHours, $activityLogger): User {
            $staff = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $email,
                'contact_number' => $validated['contact_number'] ?? null,
                'department_id' => $office->id,
                'created_by' => Auth::id(),
                'role' => 'staff',
                'is_active' => true,
                'password' => $tempPassword,
                'must_change_password' => true,
                'password_expires_at' => now()->addHours($tempExpiryHours),
            ]);

            $activityLogger->log(
                'staff.created',
                "Staff account created for {$staff->full_name} by the Department Administrator of {$office->name} via emailed temporary password.",
                $staff,
                departmentId: $office->id,
                metadata: [
                    'method' => 'emailed_temp_password',
                    'creation_method' => 'emailed_temp_password',
                    'must_change_password' => true,
                    'temp_password_expires_in_hours' => $tempExpiryHours,
                    'created_by' => Auth::id(),
                ],
            );

            return $staff;
        });

        // The password is hashed by the model cast; the plain value only
        // travels in this notification.
        $staff->notify(new StaffWelcomeNotification($tempPassword));

        $otpSent = false;
        try {
            $verificationService->issue(
                $staff,
                $staff->email,
                EmailVerificationService::PURPOSE_REGISTRATION,
            );
            $otpSent = true;
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('staff.office.index')
            ->with(
                'status',
                $otpSent
                    ? "{$staff->full_name} was added. The welcome email and verification code were sent."
                    : "{$staff->full_name} was added. The welcome email was sent, but the verification code could not be sent.",
            );
    }

    /**
     * Withdraw an invitation that has not been used yet.
     */
    public function revokeInvitation(InvitationCode $invitation, ActivityLogger $activityLogger): RedirectResponse
    {
        $office = $this->office();

        abort_unless((int) $invitation->department_id === (int) $office->id, 403, 'That invitation belongs to another office.');

        if (! $invitation->is_active || $invitation->used_count > 0) {
            return back()->with('status', 'That invitation is no longer active.');
        }

        $invitation->update(['is_active' => false]);

        $activityLogger->log(
            'invitation.revoked',
            "Invitation for {$invitation->invitee_email} was revoked by the Department Administrator of {$office->name}.",
            $invitation,
            departmentId: $office->id,
            metadata: ['invitee_email' => $invitation->invitee_email],
        );

        return back()->with('status', "Invitation for {$invitation->invitee_email} revoked.");
    }

    /**
     * Activate or deactivate an ordinary staff member of this office.
     */
    public function toggleStaffStatus(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        $office = $this->office();
        $this->assertManageableStaff($user, $office);

        $wasActive = (bool) $user->is_active;
        $user->update(['is_active' => ! $wasActive]);

        $activityLogger->log(
            $wasActive ? 'staff.deactivated' : 'staff.activated',
            "Staff account {$user->full_name} ".($wasActive ? 'deactivated' : 'activated')." by the Department Administrator of {$office->name}.",
            $user,
            departmentId: $office->id,
            metadata: ['previous_is_active' => $wasActive, 'is_active' => ! $wasActive],
        );

        return back()->with(
            'status',
            "{$user->full_name} was ".($wasActive ? 'deactivated' : 'activated').'.',
        );
    }

    /**
     * Grant or revoke the verification capability of an ordinary staff member.
     * Department Administrators already verify by role and are never a target.
     */
    public function toggleStaffVerification(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        $office = $this->office();
        $this->assertManageableStaff($user, $office);

        $wasVerifier = (bool) $user->can_verify;
        $user->update(['can_verify' => ! $wasVerifier]);

        $activityLogger->log(
            $wasVerifier ? 'staff.verification_revoked' : 'staff.verification_granted',
            'Verification rights '.($wasVerifier ? 'revoked from' : 'granted to')." {$user->full_name} by the Department Administrator of {$office->name}.",
            $user,
            departmentId: $office->id,
            metadata: ['previous_can_verify' => $wasVerifier, 'can_verify' => ! $wasVerifier],
        );

        return back()->with(
            'status',
            $user->fresh()->can_verify
                ? "{$user->full_name} can now settle complaints for this office."
                : "{$user->full_name} can no longer settle complaints.",
        );
    }

    /**
     * Assign one of the office's complaints to a member of the same office.
     * Available to office verifiers (Department Administrators and staff with
     * verification rights), scoped to their own office by policy.
     */
    public function assignComplaint(
        Request $request,
        Complaint $complaint,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        Gate::authorize('assign', $complaint);

        $actor = Auth::user();
        abort_unless($actor !== null && $actor->isDepartmentStaff() && $actor->department !== null, 403);
        $office = $actor->department;
        abort_unless($office->is_active, 403, 'This office is not active.');
        abort_unless((int) $complaint->department_id === (int) $office->id, 403);

        $validated = $request->validate([
            'assigned_staff_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $assignee = null;
        if (! empty($validated['assigned_staff_id'])) {
            $assignee = User::query()->findOrFail((int) $validated['assigned_staff_id']);

            if (! in_array($assignee->role, ['staff', 'department_admin'], true)
                || (int) $assignee->department_id !== (int) $office->id
                || ! $assignee->is_active) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => 'Choose an active staff member from this office.',
                ]);
            }
        }

        $previousAssigneeId = $complaint->assigned_staff_id;
        $previousAssignee = $previousAssigneeId !== null
            ? User::query()->find($previousAssigneeId)?->full_name
            : null;

        $actorLabel = $actor->isDepartmentAdmin()
            ? "the Department Administrator of {$office->name}"
            : "a verifier in {$office->name}";

        DB::transaction(function () use ($complaint, $assignee, $office, $previousAssigneeId, $previousAssignee, $activityLogger, $actorLabel): void {
            $complaint->update(['assigned_staff_id' => $assignee?->id]);

            $activityLogger->log(
                'complaint.assignment_updated',
                $assignee !== null
                    ? "Complaint {$complaint->ticket_id} assigned to {$assignee->full_name} by {$actorLabel}."
                    : "Complaint {$complaint->ticket_id} unassigned by {$actorLabel}.",
                $complaint,
                departmentId: $office->id,
                metadata: [
                    'previous_assigned_staff_id' => $previousAssigneeId,
                    'assigned_staff_id' => $assignee?->id,
                    'previous_assigned_staff_name' => $previousAssignee,
                    'assigned_staff_name' => $assignee?->full_name,
                ],
            );
        });

        if ($assignee !== null && (int) $previousAssigneeId !== (int) $assignee->id) {
            try {
                $notifications->complaintAssigned($complaint->fresh(), $assignee, Auth::user());
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with(
            'status',
            $assignee !== null
                ? "Complaint {$complaint->ticket_id} assigned to {$assignee->full_name}."
                : "Complaint {$complaint->ticket_id} is now unassigned.",
        );
    }

    /**
     * The office's wider audit trail: staff accounts, invitations, complaint
     * actions and automated records. Resident actions are deliberately not
     * listed here; the public privacy boundary keeps anonymous complainants
     * masked, and the office already sees complaint activity without identity.
     */
    public function auditIndex(): View
    {
        $office = $this->office();

        $logs = ActivityLog::query()
            ->with(['actor:id,first_name,last_name,role', 'department'])
            ->where('department_id', $office->id)
            ->where(function ($query): void {
                $query->whereIn('actor_role', ['staff', 'department_admin', 'admin'])
                    ->orWhereNull('actor_role');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::AUDIT_PAGE_SIZE)
            ->withQueryString();

        return view('staff.office.audit', compact('office', 'logs'));
    }

    /**
     * Office-scoped statistics for Department Administrators only.
     */
    public function statistics(Request $request): View
    {
        return view('staff.office.statistics', $this->statisticsPayload($request));
    }

    public function statisticsBrowser(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wantsFragments($request)) {
            return redirect($this->fragmentFallbackUrl($request, 'staff.office.statistics.index'));
        }

        $payload = $this->statisticsPayload($request);

        return response()->json([
            'period' => view('analytics.partials.period', $payload)->render(),
            'content' => view('staff.office.partials.statistics-content', $payload)->render(),
        ]);
    }

    public function statisticsExport(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $office = $this->office();
        $period = AnalyticsPeriod::fromRequest($request);

        return $this->analytics->export($office, $period);
    }

    public function statisticsPrint(Request $request): View
    {
        $payload = $this->statisticsPayload($request);

        return view('analytics.print', array_merge($payload, [
            'surface' => 'staff',
            'staff_breakdown' => true,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function statisticsPayload(Request $request): array
    {
        $office = $this->office();
        $period = AnalyticsPeriod::fromRequest($request);
        $data = $this->analytics->department($office, $period, withStaff: true);

        $drilldown = [
            'filed' => route('staff.complaints.index'),
            'open' => route('staff.complaints.index', ['scope' => 'open']),
            'resolved' => route('staff.complaints.index', ['scope' => 'finished', 'status' => 'resolved']),
            'closed' => route('staff.complaints.index', ['scope' => 'finished', 'status' => 'closed']),
            'rejected' => route('staff.complaints.index', ['scope' => 'finished', 'status' => 'rejected']),
            'overdue' => route('staff.complaints.index', ['overdue' => 1]),
            'pending' => route('staff.complaints.index', ['review' => 'pending']),
            'needs_information' => route('staff.complaints.index', ['review' => 'needs_information']),
            'in_progress' => route('staff.complaints.index', ['status' => 'in_progress']),
            'submitted' => route('staff.complaints.index', ['status' => 'submitted']),
            'under_review' => route('staff.complaints.index', ['status' => 'under_review']),
            'on_hold' => route('staff.complaints.index', ['status' => 'on_hold']),
        ];

        return [
            'office' => $office,
            'department' => $office,
            'period' => $period,
            'data' => $data,
            'staffBreakdown' => true,
            'drilldown' => $drilldown,
            'routeNames' => [
                'page' => 'staff.office.statistics.index',
                'browser' => 'staff.office.statistics.browser',
                'export' => 'staff.office.statistics.export',
                'print' => 'staff.office.statistics.print',
            ],
            'routeParams' => [],
        ];
    }

    /**
     * The signed-in Department Administrator's active office.
     */
    private function office(): Department
    {
        $user = Auth::user();

        abort_unless($user !== null && $user->isDepartmentAdmin(), 403, 'Only a Department Administrator can manage an office.');

        $department = $user->department;

        abort_if($department === null, 403, 'Your account is not assigned to an office yet.');
        abort_unless($department->is_active, 403, 'This office is not active.');

        return $department;
    }

    /**
     * A manager may act on ordinary staff of the same office only. Peers and
     * themselves are protected by the role check, and no action in this
     * controller changes a profile, a department, or a password.
     */
    private function assertManageableStaff(User $user, Department $office): void
    {
        abort_unless($user->role === 'staff', 403, 'Department Administrators can only manage staff accounts.');
        abort_unless((int) $user->department_id === (int) $office->id, 403, 'That account belongs to another office.');
        abort_if($user->is(Auth::user()), 403, 'You cannot manage your own account.');
    }

    /**
     * Create and deliver a single-use invitation for this office.
     */
    private function issueInvitation(
        Department $office,
        string $fullName,
        string $email,
        int $expiryHours,
        ActivityLogger $activityLogger,
        StaffInvitationDelivery $invitationDelivery,
    ): RedirectResponse {
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

        $invitation = DB::transaction(function () use ($office, $fullName, $email, $expiryHours, $activityLogger): InvitationCode {
            do {
                $code = Str::random(32);
            } while (InvitationCode::where('code', $code)->exists());

            $invitation = InvitationCode::create([
                'code' => $code,
                'invitee_name' => $fullName,
                'invitee_email' => $email,
                'department_id' => $office->id,
                'created_by' => Auth::id(),
                'expires_at' => now()->addHours($expiryHours),
                'max_uses' => 1,
                'used_count' => 0,
                'is_active' => true,
            ]);

            $activityLogger->log(
                'staff.created',
                "Staff invitation created for {$fullName} ({$email}) by the Department Administrator of {$office->name}.",
                $invitation,
                departmentId: $office->id,
                metadata: [
                    'method' => 'invitation',
                    'creation_method' => 'invitation',
                    'expires_in_hours' => $expiryHours,
                    'created_by' => Auth::id(),
                ],
            );

            $activityLogger->log(
                'invitation.generated',
                "Single-use staff invitation generated for {$office->name}, addressed to {$email}.",
                $invitation,
                departmentId: $office->id,
                metadata: [
                    'method' => 'invitation',
                    'expires_in_hours' => $expiryHours,
                ],
            );

            return $invitation;
        });

        try {
            $invitationDelivery->send($invitation, route('invitations.show', $invitation->code));
            $invitation->forceFill(['emailed_at' => now()])->save();

            return redirect()
                ->route('staff.office.index')
                ->with('status', "Invitation emailed to {$email}. The link expires in {$expiryHours} hours and can be used once.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('staff.office.index')
                ->with('error', "The invitation for {$email} was created but the email could not be sent. Copy its link from the invitation list and share it manually.");
        }
    }
}
