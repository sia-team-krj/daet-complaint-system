<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\VerificationMethod;
use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use App\Http\Controllers\Concerns\SoftNavigation;
use App\Services\ActivityLogger;
use App\Services\ComplaintModerationService;
use App\Services\ComplaintSlaService;
use App\Services\ComplaintVerificationService;
use App\Services\ComplaintWorkService;
use App\Services\NotificationService;
use App\Support\QueueFingerprint;
use App\Support\TicketFingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StaffController extends Controller
{
    use SoftNavigation;

    /**
     * Get department-scoped complaint query builder
     */
    private function departmentComplaintsQuery()
    {
        $user = Auth::user();
        $departmentId = $user->department_id;

        // Columns are restricted on staff surfaces: a resident travels to every
        // staff page as the staff-safe alias plus the barangay the report was
        // filed about — never as the full model with its email and contact
        // number (see ComplaintPolicy::viewRealIdentity).
        $query = Complaint::with([
            'user:id,display_alias,barangay',
            'department',
            'logs.actor:id,display_alias,first_name,last_name,role',
        ]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query;
    }

    /**
     * Staff dashboard with department complaint queue and my-activity tabs
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        if ($request->get('tab') === 'my-activity') {
            return redirect()->route('staff.activity.index');
        }

        $user = Auth::user();
        $department = $user->department;
        $departmentId = $user->department_id;
        $baseQuery = Complaint::where('department_id', $departmentId);

        $stats = [
            'new' => (clone $baseQuery)->where('review_status', ComplaintReviewStatus::Pending->value)->count(),
            'inProgress' => (clone $baseQuery)->where('status', ComplaintStatus::InProgress->value)->count(),
            'resolvedThisMonth' => (clone $baseQuery)
                ->where('status', ComplaintStatus::Resolved->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'avgDays' => $this->calculateAvgResolutionDays($departmentId),
        ];

        $priorityFilter = $request->string('priority_filter')->toString();
        $prioritySearch = $request->string('q')->toString();

        $openPriorityBase = (clone $baseQuery)->whereIn('status', [
            ComplaintStatus::Submitted->value,
            ComplaintStatus::UnderReview->value,
            ComplaintStatus::InProgress->value,
        ]);
        $priorityCounts = [
            'all' => (clone $openPriorityBase)->count(),
            'pending' => (clone $openPriorityBase)->where('review_status', ComplaintReviewStatus::Pending->value)->count(),
            'needs_review' => (clone $openPriorityBase)->where('review_status', ComplaintReviewStatus::NeedsInformation->value)->count(),
            'possible_duplicate' => (clone $openPriorityBase)->where(function ($query) {
                $query->where('review_status', ComplaintReviewStatus::Duplicate->value)
                    ->orWhereNotNull('duplicate_of_id')
                    ->orWhereIn('spam_status', ['review', 'spam']);
            })->count(),
        ];

        $priorityQuery = (clone $openPriorityBase)->with(['user:id,display_alias,barangay']);

        if ($priorityFilter === 'pending') {
            $priorityQuery->where('review_status', ComplaintReviewStatus::Pending->value);
        } elseif ($priorityFilter === 'needs_review') {
            $priorityQuery->where('review_status', ComplaintReviewStatus::NeedsInformation->value);
        } elseif ($priorityFilter === 'possible_duplicate') {
            $priorityQuery->where(function ($query) {
                $query->where('review_status', ComplaintReviewStatus::Duplicate->value)
                    ->orWhereNotNull('duplicate_of_id')
                    ->orWhereIn('spam_status', ['review', 'spam']);
            });
        }

        if ($prioritySearch !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prioritySearch).'%';
            $priorityQuery->where(function ($query) use ($like) {
                $query->where('ticket_id', 'ILIKE', $like)
                    ->orWhere('title', 'ILIKE', $like)
                    ->orWhere('category', 'ILIKE', $like);
            });
        }

        $priorityComplaints = $priorityQuery
            ->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE COALESCE(confirmed_priority, suggested_priority) WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 WHEN 'elevated' THEN 2 ELSE 3 END")
            ->oldest('created_at')
            ->limit(3)
            ->get();

        $rawActivity = ComplaintLog::with([
            'complaint',
            'actor:id,display_alias,first_name,last_name,role',
        ])
            ->whereHas('complaint', fn ($query) => $query->where('department_id', $departmentId))
            ->latest('created_at')
            ->limit(12)
            ->get();

        $recentActivity = collect();
        $group = null;
        $flush = function () use (&$group, &$recentActivity) {
            if ($group === null) {
                return;
            }
            $recentActivity->push($this->formatActivityGroup($group));
            $group = null;
        };
        foreach ($rawActivity as $log) {
            $ticketId = $log->complaint?->ticket_id ?? null;
            if ($group === null || $group['ticket_id'] !== $ticketId) {
                $flush();
                $group = ['ticket_id' => $ticketId, 'logs' => [$log]];
            } else {
                $group['logs'][] = $log;
            }
            if ($recentActivity->count() >= 4) {
                break;
            }
        }
        $flush();
        return view('staff.dashboard.index', compact(
            'department',
            'stats',
            'priorityComplaints',
            'priorityCounts',
            'recentActivity',
            'user',
            'priorityFilter',
            'prioritySearch'
        ));
    }

    /**
     * My Activity tab - complaints this staff member has handled
     */
    public function activityIndex(Request $request): View
    {
        $user = Auth::user();
        $department = $user->department;
        $departmentId = $user->department_id;
        $handledComplaintIds = ComplaintLog::where('actor_id', $user->id)
            ->whereHas('complaint', fn ($query) => $query->where('department_id', $departmentId))
            ->pluck('complaint_id')
            ->unique()
            ->values();

        $stats = [
            'totalResponded' => $handledComplaintIds->count(),
            'resolvedByMe' => ComplaintLog::where('actor_id', $user->id)
                ->where('new_status', ComplaintStatus::Resolved->value)
                ->count(),
            'lastActivity' => ComplaintLog::where('actor_id', $user->id)
                ->latest('created_at')
                ->first()?->created_at,
        ];

        $complaints = Complaint::whereIn('id', $handledComplaintIds)
            ->with(['user:id,display_alias,barangay', 'logs' => fn ($query) => $query
                ->where('actor_id', $user->id)
                ->latest('created_at')
                ->limit(1)])
            ->orderByDesc(
                ComplaintLog::select('created_at')
                    ->whereColumn('complaint_logs.complaint_id', 'complaints.id')
                    ->where('complaint_logs.actor_id', $user->id)
                    ->latest()
                    ->limit(1)
            )
            ->paginate(10)
            ->withQueryString();

        return view('staff.activity.index', compact('department', 'stats', 'complaints', 'user'));
    }

    /**
     * Display-only formatting for recent activity messages (does not alter stored data).
     *
     * @return array{detail:string,note:?string}
     */
    private function formatActivityDisplay(string $raw, ?string $newStatus): array
    {
        $note = null;
        if (preg_match('/Review notes:\s*(.+)$/iu', $raw, $m)) {
            $candidate = trim($m[1]);
            if ($candidate !== '' && $candidate !== '?') {
                $note = implode(' · ', array_unique(array_map('trim', explode(' · ', $candidate))));
            }
        }

        // Detail says only what changed: the status transition. Department names
        // and free-form comment text are intentionally not repeated here.
        if ($newStatus) {
            $label = ComplaintStatus::tryFrom($newStatus)?->label() ?? $newStatus;
            $detail = $label ? 'Status: '.$label : __('staff.dashboard.activity_status_changed');
        } else {
            $detail = __('staff.dashboard.activity_status_changed');
        }

        return ['detail' => $detail, 'note' => $note];
    }
    private function activityVerb(?string $newStatus): string
    {
        return match ($newStatus) {
            'submitted' => __('staff.dashboard.verb_filed'),
            'under_review', 'verified', 'assigned' => __('staff.dashboard.verb_reviewed'),
            'in_progress' => __('staff.dashboard.verb_assigned'),
            'resolved' => __('staff.dashboard.verb_resolved'),
            'closed' => __('staff.dashboard.verb_closed'),
            default => __('staff.dashboard.verb_updated'),
        };
    }

    private function formatActivityGroup(array $group): \stdClass
    {
        $logs = array_values($group['logs']);
        $latest = end($logs);
        $actorName = $latest->actor?->staff_actor_name ?? __('staff.dashboard.activity_actor_fallback');
        $ticket = $latest->complaint?->ticket_id ?? __('staff.dashboard.activity_ticket_fallback');
        $count = count($logs);
        $title = __('staff.dashboard.activity_grouped', [
            'actor' => $actorName,
            'verb' => $this->activityVerb($latest->new_status),
            'ticket' => $ticket,
        ]);
        if ($latest->new_status) {
            $label = ComplaintStatus::tryFrom($latest->new_status)?->label() ?? $latest->new_status;
            $detail = $label ? 'Status: '.$label : __('staff.dashboard.activity_status_changed');
        } else {
            $detail = __('staff.dashboard.activity_status_changed');
        }
        $note = null;
        $raw = trim((string) ($latest->comment ?? ''));
        if (preg_match('/Review notes:\s*(.+)$/iu', $raw, $m)) {
            $candidate = trim($m[1]);
            if ($candidate !== '' && $candidate !== '?') {
                $note = implode(' · ', array_unique(array_map('trim', explode(' · ', $candidate))));
            }
        }
        $entry = new \stdClass();
        $entry->display_title = $title;
        $entry->display_detail = $detail;
        $entry->display_note = $note;
        $entry->display_count = $count > 1 ? $count : null;
        $entry->created_at = $latest->created_at;
        $entry->complaint = $latest->complaint;
        return $entry;
    }
    /**
     * Calculate average resolution days for department
     */
    private function calculateAvgResolutionDays(?int $departmentId): ?float
    {
        $query = Complaint::where('status', ComplaintStatus::Resolved->value);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $avgDays = $query
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days')
            ->first()
            ->avg_days;

        return $avgDays ? round($avgDays, 1) : null;
    }

    public function complaintsIndex(Request $request, ComplaintSlaService $sla): View
    {
        $user = Auth::user();
        $department = $user->department;
        $status = $request->get('status');
        $reviewStatus = $request->get('review');
        $priority = $request->get('priority');
        $search = $request->get('search');
        $overdue = $request->get('overdue') === '1';

        $complaints = $this->complaintsQueue($request, $user, $sla);
        $queueFingerprint = QueueFingerprint::for($complaints);

        $stats = [
            'new' => Complaint::where('department_id', $user->department_id)->where('review_status', ComplaintReviewStatus::Pending->value)->count(),
            'inProgress' => Complaint::where('department_id', $user->department_id)->where('status', ComplaintStatus::InProgress->value)->count(),
            'resolvedThisMonth' => Complaint::where('department_id', $user->department_id)
                ->where('status', ComplaintStatus::Resolved->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'avgDays' => $this->calculateAvgResolutionDays($user->department_id),
        ];

        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

        // Where each complaint stands against its deadline, so the row can say
        // how long the office has left rather than only where it is.
        $slaSummaries = $this->slaSummariesFor($complaints);

        return view('staff.complaints.index', compact(
            'department',
            'stats',
            'complaints',
            'status',
            'reviewStatus',
            'priority',
            'reviewStatusOptions',
            'priorityOptions',
            'search',
            'user',
            'slaSummaries',
            'queueFingerprint',
            'overdue'
        ));
    }

    /**
     * Where each complaint in the queue stands against its deadline.
     *
     * Shared by the page and the live-queue endpoint, so a swapped-in row can
     * never disagree with the page it was swapped into.
     *
     * @return array<int, array<string, mixed>>
     */
    private function slaSummariesFor($complaints): array
    {
        $sla = app(ComplaintSlaService::class);

        return $complaints->getCollection()
            ->mapWithKeys(fn (Complaint $complaint): array => [
                $complaint->id => $sla->summary($complaint),
            ])
            ->all();
    }

    /**
     * The staff queue's rows.
     *
     * Shared by the page and the live-queue endpoint, so a fragment can never
     * disagree with the page it was swapped into.
     */
    private function complaintsQueue(Request $request, User $user, ComplaintSlaService $sla)
    {
        $departmentId = $user->department_id;
        $status = $request->get('status');
        $reviewStatus = $request->get('review');
        $priority = $request->get('priority');
        $search = $request->get('search');
        $overdue = $request->get('overdue') === '1';
        $validStatuses = [
            ComplaintStatus::Submitted->value,
            ComplaintStatus::UnderReview->value,
            ComplaintStatus::InProgress->value,
            ComplaintStatus::Resolved->value,
            ComplaintStatus::Rejected->value,
        ];

        $validReviewStatuses = array_map(
            fn (ComplaintReviewStatus $review): string => $review->value,
            ComplaintReviewStatus::cases(),
        );

        $validPriorities = array_map(
            fn (ComplaintPriority $priority): string => $priority->value,
            ComplaintPriority::cases(),
        );

        // Outstanding asks are eager-loaded, otherwise the queue would issue one
        // extra query per row just to show how long a resident has been waiting.
        $query = Complaint::where('department_id', $departmentId)->with([
            'user:id,display_alias,barangay',
            'informationRequests' => fn ($relation) => $relation
                ->outstanding()
                ->latest('id'),
        ]);

        if ($status && in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        if ($reviewStatus && in_array($reviewStatus, $validReviewStatuses, true)) {
            $query->where('review_status', $reviewStatus);
        }

        if ($priority && in_array($priority, $validPriorities, true)) {
            $query->where('confirmed_priority', $priority);
        }

        if ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('ticket_id', 'ILIKE', '%'.$search.'%')
                    ->orWhere('title', 'ILIKE', '%'.$search.'%')
                    ->orWhere('description', 'ILIKE', '%'.$search.'%');
            });
        }

        // Only the work that has actually slipped past its deadline. The days
        // depend on the priority, so the ids come back from the clock rather
        // than from a plain interval.
        if ($overdue) {
            $query->whereIn('complaints.id', $sla->overdueIds($departmentId));
        }

        return $query
            ->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE COALESCE(confirmed_priority, suggested_priority) WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 WHEN 'elevated' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->paginate(3)
            ->withQueryString();
    }

    /**
     * The staff queue's regions, for the live queue.
     *
     * A staff member who leaves the queue open must see that a case was moved
     * or closed by someone else. The endpoint answers with the rendered rows
     * and a fingerprint: the browser compares the fingerprint and swaps the
     * rows only when something really changed.
     */
    public function complaintsBrowser(Request $request, ComplaintSlaService $sla): JsonResponse|RedirectResponse
    {
        // A person gets the page; only a script gets the fragments.
        if (! $this->wantsFragments($request)) {
            return redirect()->to($this->fragmentFallbackUrl($request, 'staff.complaints.index'));
        }

        $complaints = $this->complaintsQueue($request, Auth::user(), $sla);
        $slaSummaries = $this->slaSummariesFor($complaints);

        return response()->json([
            'list' => view('staff.complaints.partials.list', [
                'complaints' => $complaints,
                'slaSummaries' => $slaSummaries,
            ])->render(),
            'pagination' => view('staff.complaints.partials.pagination', [
                'complaints' => $complaints,
            ])->render(),
            'fingerprint' => QueueFingerprint::for($complaints),
        ]);
    }

    /**
     * The complaint's current state, for the live ticket panel.
     *
     * A staff member who opens a ticket and starts writing a response can
     * easily spend several minutes on it. In that time somebody else in the
     * office may move the case, settle it, or leave a note. Without this the
     * page says nothing, and the staff member finds out by being refused.
     *
     * The endpoint answers with a fingerprint of what changed and who changed
     * it. It never rewrites the page: a staff member who is typing must not
     * have the form pulled out from under them. A person who opens the URL in
     * the address bar is sent to the ticket instead of being shown JSON.
     */
    public function complaintState(Complaint $complaint, Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wantsFragments($request)) {
            return redirect()->to(route('staff.complaints.show', $complaint));
        }

        $user = Auth::user();

        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            abort(403, 'This complaint is not assigned to your department.');
        }

        $complaint->load(['logs.actor:id,display_alias,first_name,last_name,role']);

        $fingerprint = TicketFingerprint::for($complaint);
        $rendered = (string) $request->query('fingerprint', '');

        if ($fingerprint === $rendered) {
            return response()->json(['fingerprint' => $fingerprint, 'changed' => false]);
        }

        $newest = $complaint->logs->sortByDesc('id')->first();
        $previous = $newest?->previous_status !== null
            ? ComplaintStatus::tryFrom($newest->previous_status)
            : null;
        $movedTo = $newest !== null
            ? ComplaintStatus::tryFrom((string) $newest->new_status)
            : null;

        // The resident is reported by their staff-safe alias, never their real
        // name — the same masking the timeline keeps (§ staff-safe identity).
        $message = match (true) {
            $newest === null => __('staff.live.changed'),
            $newest->is_internal && $newest->actor !== null => __('staff.live.note', [
                'actor' => $newest->actor->staff_actor_name,
            ]),
            $newest->actor === null => __('staff.live.changed'),
            $previous !== null && $movedTo !== null && $previous !== $movedTo => __('staff.live.moved', [
                'actor' => $newest->actor->staff_actor_name,
                'previous' => $previous->label(),
                'status' => $movedTo->label(),
            ]),
            default => __('staff.live.updated', ['actor' => $newest->actor->staff_actor_name]),
        };

        return response()->json([
            'fingerprint' => $fingerprint,
            'changed' => true,
            'status' => $complaint->status,
            'status_label' => $complaint->statusEnum->label(),
            'status_key' => Str::slug((string) $complaint->status, '_'),
            'actor' => $newest?->actor?->staff_actor_name,
            'occurred_at' => $newest?->created_at?->toAtomString(),
            'message' => $message,
        ]);
    }

    /**
     * Show single complaint details for staff
     */
    public function show(Complaint $complaint, ComplaintWorkService $work, ComplaintSlaService $sla): View
    {
        $user = Auth::user();

        // Authorization: staff can only view their department's complaints
        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            abort(403, 'This complaint is not assigned to your department.');
        }

        // The resident rides as the staff-safe alias plus the barangay the
        // report named — never as the full model with its email or contact
        // number. Staff actors load their name columns so the page still says
        // who verified, reviewed or delegated.
        $complaint->load([
            'user:id,display_alias,barangay',
            'department',
            'logs.actor:id,display_alias,first_name,last_name,role',
            'logs.evidences',
            'reviewedBy',
            'verifyingStaff',
            'informationRequests.requester:id,display_alias,first_name,last_name,role',
        ]);

        // Status options for dropdown (excluding current status to force change)
        $statuses = array_filter(ComplaintStatus::cases(), fn ($s) => $s->value !== $complaint->statusEnum->value);
        // The review decision is a verdict on an existing report. Asking the
        // resident for more evidence is a different, dedicated flow that
        // always carries the question — never this dropdown.
        $reviewStatusOptions = array_values(array_filter(
            ComplaintReviewStatus::cases(),
            fn ($option) => $option !== ComplaintReviewStatus::NeedsInformation
        ));
        $priorityOptions = ComplaintPriority::cases();

        // What kind of work this is, and therefore what the office has to
        // attach before it can move the complaint on.
        $workMode = $work->modeFor($complaint);
        $documentationRequiredStatuses = array_map(
            fn (ComplaintStatus $status): string => $status->value,
            $work->documentationRequiredStatuses($complaint),
        );

        // Where the office stands against the deadline it took on when it
        // verified this report.
        $slaSummary = $sla->summary($complaint);

        // Only offered when the viewer can actually verify, so the page does not
        // advertise a control that would be refused.
        $departmentVerifiers = $user->canVerifyComplaints()
            ? User::query()
                ->where('role', 'staff')
                ->where('department_id', $complaint->department_id)
                ->where('can_verify', true)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->get()
            : collect();

        // The conversation with the resident: what is outstanding, and which
        // round it is on. The ask and the answer are one state, so the page
        // never shows a fresh question box while the resident is still replying.
        $openInformationRequest = $complaint->informationRequests
            ->sortByDesc('id')
            ->first(fn ($informationRequest): bool => $informationRequest->isOpen()
                || $informationRequest->isAnswered());

        $informationRounds = (int) $complaint->informationRequests->max('round');

        $awaitingResident = ($openInformationRequest?->isOpen() ?? false)
            || ($openInformationRequest === null
                && $complaint->review_status === ComplaintReviewStatus::NeedsInformation->value);

        // Verification follow-up only makes sense while the report is still
        // being settled. Once it is verified, the work has begun.
        $verificationOpen = in_array($complaint->statusEnum, [
            ComplaintStatus::Submitted,
            ComplaintStatus::UnderReview,
        ], true);

        $delegationCandidates = $departmentVerifiers
            ->reject(fn (User $verifier): bool => $verifier->id === $user->id
                || $verifier->id === $complaint->assigned_staff_id)
            ->values();

        $canAskForInformation = $user->canVerifyComplaints()
            && $verificationOpen
            && $openInformationRequest === null
            && $informationRounds < ComplaintVerificationService::MAX_INFORMATION_ROUNDS;

        $canDelegateVerification = $user->canVerifyComplaints()
            && $verificationOpen
            && $delegationCandidates->isNotEmpty();

        // What the page is showing, so the live panel can tell whether the
        // ticket moved while the staff member was reading it.
        $ticketFingerprint = TicketFingerprint::for($complaint);

        return view('staff.complaints.show', compact(
            'complaint',
            'statuses',
            'reviewStatusOptions',
            'priorityOptions',
            'departmentVerifiers',
            'openInformationRequest',
            'informationRounds',
            'awaitingResident',
            'delegationCandidates',
            'verificationOpen',
            'canAskForInformation',
            'canDelegateVerification',
            'user',
            'workMode',
            'documentationRequiredStatuses',
            'slaSummary',
            'ticketFingerprint'
        ));
    }

    /**
     * Review complaint legitimacy and confirm the operational priority.
     */
    public function review(Request $request, Complaint $complaint, ActivityLogger $activityLogger, ComplaintVerificationService $verification, NotificationService $notifications): RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            abort(403, 'This complaint is not assigned to your department.');
        }

        $validated = $request->validate([
            'review_status' => ['required', Rule::enum(ComplaintReviewStatus::class)],
            'confirmed_priority' => ['nullable', Rule::enum(ComplaintPriority::class)],
            'verification_method' => ['nullable', Rule::enum(VerificationMethod::class)],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            // is_public is deliberately absent. Whether a complaint is public is
            // the resident's decision; staff moderate content through the
            // separate moderation action instead.
        ]);

        $reviewStatus = ComplaintReviewStatus::from($validated['review_status']);
        $confirmedPriority = isset($validated['confirmed_priority'])
            ? ComplaintPriority::from($validated['confirmed_priority'])
            : null;
        $method = isset($validated['verification_method'])
            ? VerificationMethod::from($validated['verification_method'])
            : null;

        // Verification used to be open to any staff member in the department.
        // It is now a capability, and the assigned worker can never sign off
        // on their own assignment.
        try {
            $verification->authorize($user, $complaint);
            $verification->authorizeNoSelfConflict($complaint, $user->id);

            $updates = $verification->reviewUpdates(
                $complaint,
                $user,
                $reviewStatus,
                $confirmedPriority,
                $method,
                $validated['review_notes'] ?? $complaint->review_notes,
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }

        $oldReviewStatus = $complaint->reviewStatusEnum;
        $oldPriority = $complaint->confirmedPriorityEnum;
        $oldStatus = $complaint->statusEnum;

        DB::transaction(function () use ($complaint, $updates, $oldStatus, $reviewStatus, $confirmedPriority, $oldReviewStatus, $oldPriority, $activityLogger): void {
            $complaint->update($updates);

            $priorityText = $confirmedPriority?->label() ?? 'Not confirmed';
            $comment = "Department review: {$reviewStatus->label()}; confirmed priority: {$priorityText}.";
            if (! empty($complaint->review_notes)) {
                $comment .= ' Review notes: '.$complaint->review_notes;
            }

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $oldStatus->value,
                'new_status' => $updates['status'] ?? $oldStatus->value,
                'comment' => $comment,
                'actor_id' => auth()->id(),
                'is_internal' => false,
            ]);

            $activityLogger->log(
                'complaint.reviewed',
                "Complaint {$complaint->ticket_id} moved from {$oldReviewStatus->label()} to {$reviewStatus->label()}.",
                $complaint,
                metadata: [
                    'previous_review_status' => $oldReviewStatus->value,
                    'review_status' => $reviewStatus->value,
                    'previous_confirmed_priority' => $oldPriority?->value,
                    'confirmed_priority' => $confirmedPriority?->value,
                ],
            );
        });

        // A review decision is the event the resident is waiting for. Sending it
        // is secondary to saving the decision, so a notification failure must
        // not make the review look like it did not land.
        if ($oldReviewStatus !== $reviewStatus) {
            try {
                $notifications->reviewUpdated($complaint, $reviewStatus, $user);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', "Complaint review saved as {$reviewStatus->label()}.");
    }

    /**
     * Withhold or redact a complaint's public presentation.
     *
     * Separate from the review decision on purpose. A reviewer decides whether a
     * report is accurate; neither they nor an admin decides whether a resident's
     * complaint appears publicly. That is the resident's call. What staff can do
     * here is stop genuinely inappropriate content being broadcast — and every
     * use of that power is reason-bearing and logged.
     */
    public function moderate(
        Request $request,
        Complaint $complaint,
        ComplaintModerationService $moderation,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $user = Auth::user();

        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            return back()->with('error', 'You cannot moderate complaints outside your department.');
        }

        $validated = $request->validate([
            'hidden_from_public' => ['nullable', 'boolean'],
            'redacted_title' => ['nullable', 'string', 'max:255'],
            'redacted_description' => ['nullable', 'string', 'max:5000'],
            'withheld_image_paths' => ['nullable', 'array'],
            'withheld_image_paths.*' => ['string'],
            'restore_image_paths' => ['nullable', 'array'],
            'restore_image_paths.*' => ['string'],
            'moderation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updates = $moderation->apply($complaint, $user, $validated);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }

        if ($updates === []) {
            return back()->with('error', 'Nothing was selected to moderate.');
        }

        $complaint->refresh();

        $activityLogger->log(
            'complaint.moderated',
            "Complaint {$complaint->ticket_id}: ".$moderation->summary($complaint, $updates).'. Reason: '.$updates['moderation_reason'],
            $complaint,
            metadata: [
                'hidden_from_public' => $updates['hidden_from_public'],
                'redacted_title' => $updates['redacted_title'] !== null,
                'redacted_description' => $updates['redacted_description'] !== null,
                'withheld_photo_count' => count($updates['withheld_image_paths']),
                // Recorded so the audit trail shows the consent was left alone.
                'resident_visibility_untouched' => true,
            ],
        );

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', 'Moderation saved. The resident still sees their complaint exactly as they filed it.');
    }

    /**
     * Update complaint status and add a public response with optional internal note.
     *
     * What the action demands depends on the kind of work the complaint is
     * (ComplaintWorkService): a repair is proven by a photograph of it, and
     * the same rule applies to an administrator as to a department.
     */
    public function update(Request $request, Complaint $complaint, ActivityLogger $activityLogger, NotificationService $notifications, ComplaintWorkService $work): RedirectResponse
    {
        $user = Auth::user();

        // Authorization check
        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            return back()->with('error', 'You cannot update complaints outside your department.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'note' => ['required', 'string', 'min:10', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'documentation_images' => ['nullable', 'array', 'max:5'],
            'documentation_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'documentation_camera_photos' => ['nullable', 'array', 'max:5'],
            'documentation_camera_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            // Accept the original singular field while older forms are in circulation.
            'documentation_camera_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldStatus = $complaint->statusEnum;
        $newStatus = ComplaintStatus::from($validated['status']);

        if (! $complaint->isVerified() && $newStatus !== ComplaintStatus::Submitted) {
            return back()->with('error', 'This complaint must pass department review before its status can advance.');
        }

        // Prevent status changes on terminal states (Rejected, Closed)
        if ($oldStatus === ComplaintStatus::Rejected || $oldStatus === ComplaintStatus::Closed) {
            return back()->with('error', 'Cannot update a complaint that is already terminal (Rejected or Closed).');
        }

        // Enforce valid transitions using the enum's allowedTransitions
        if (! $oldStatus->canTransitionTo($newStatus)) {
            return back()->with('error', 'Invalid status transition from '.$oldStatus->label().' to '.$newStatus->label().'.');
        }

        $documentationPhotos = $work->collectPhotos($request);

        // The rule: labor work is proven by a photograph, and a written
        // message is never a substitute for one. The mode this complaint is
        // judged by is settled now, so a later policy change cannot move the
        // goalposts on a ticket already in flight.
        $work->freezeMode($complaint);

        try {
            $work->assertActionAllowed(
                $complaint,
                $newStatus,
                count($documentationPhotos),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }

        if (count($documentationPhotos) > 5) {
            return back()->withErrors([
                'documentation_images' => 'You can attach a maximum of five documentation photos.',
            ]);
        }

        // The photographs are written first, and the rows that record where
        // and when they were taken are attached afterwards, inside the same
        // transaction as the update they belong to.
        try {
            $evidence = $work->storeEvidence($documentationPhotos, $complaint, $newStatus);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'documentation_images' => 'We could not save the documentation photos. Please try again.',
            ]);
        }

        $documentationPaths = $evidence['paths'];

        try {
            DB::transaction(function () use ($complaint, $oldStatus, $newStatus, $validated, $user, $activityLogger, $documentationPaths, $evidence, $work): void {
                $updates = [
                    'status' => $newStatus->value,
                ];

                if ($newStatus === ComplaintStatus::Resolved && $complaint->resolved_at === null) {
                    $updates['resolved_at'] = now();
                }

                if ($newStatus === ComplaintStatus::Closed) {
                    $updates['closed_at'] = now();
                    $updates['closed_by'] = $user->role === 'admin' ? 'admin' : 'staff';
                }

                $complaint->update($updates);

                $log = ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'previous_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'comment' => $validated['note'],
                    'documentation_paths' => $documentationPaths !== [] ? $documentationPaths : null,
                    'actor_id' => $user->id,
                    'is_internal' => false,
                ]);

                // Each photograph now has a row of its own: where it was
                // taken, when, and by whom.
                $work->attachEvidence($log, $evidence['rows'], $user);

                $activityLogger->log(
                    'complaint.status_updated',
                    "Complaint {$complaint->ticket_id} moved from {$oldStatus->label()} to {$newStatus->label()}.",
                    $complaint,
                    metadata: [
                        'previous_status' => $oldStatus->value,
                        'new_status' => $newStatus->value,
                        'public_note' => $validated['note'],
                        'documentation_count' => count($documentationPaths),
                        'documentation_located' => count(array_filter(
                            $evidence['rows'],
                            fn (array $row): bool => $row['latitude'] !== null,
                        )),
                    ],
                );

                if (! empty($validated['internal_note'])) {
                    ComplaintLog::create([
                        'complaint_id' => $complaint->id,
                        'previous_status' => null,
                        'new_status' => $oldStatus->value,
                        'comment' => $validated['internal_note'],
                        'actor_id' => $user->id,
                        'is_internal' => true,
                    ]);

                    $activityLogger->log(
                        'complaint.internal_note_added',
                        "Internal note added to complaint {$complaint->ticket_id}.",
                        $complaint,
                        metadata: ['note' => $validated['internal_note']],
                    );
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($documentationPaths);
            throw $exception;
        }

        // In-app first, so the resident learns of the change without having to
        // remember to open the page. Reported rather than thrown: a failed
        // notification must not undo a status change the department has already
        // committed to, with its proof photos attached.
        try {
            $notifications->statusChanged($complaint, $newStatus->label(), $user);
        } catch (Throwable $exception) {
            report($exception);
        }

        // Email is best-effort for the same reason. A dead SMTP host used to
        // abort the request after the status was already saved and before the
        // in-app notification was created, leaving the resident with neither.
        try {
            $recipient = $complaint->user;

            if ($recipient?->email) {
                Mail::to($recipient->email)->send(
                    new ComplaintUpdatedMail($complaint, $newStatus->value, $validated['note'])
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', 'Response sent and status updated successfully.');
    }
}
