<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StaffController extends Controller
{
    /**
     * Get department-scoped complaint query builder
     */
    private function departmentComplaintsQuery()
    {
        $user = Auth::user();
        $departmentId = $user->department_id;
        
        $query = Complaint::with(['user', 'department', 'logs.actor']);
        
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

        $priorityComplaints = (clone $baseQuery)
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])
            ->with(['user'])
            ->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE COALESCE(confirmed_priority, suggested_priority) WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 WHEN 'elevated' THEN 2 ELSE 3 END")
            ->oldest('created_at')
            ->limit(5)
            ->get();

        $recentActivity = ComplaintLog::with(['complaint', 'actor'])
            ->whereHas('complaint', fn ($query) => $query->where('department_id', $departmentId))
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('staff.dashboard.index', compact(
            'department',
            'stats',
            'priorityComplaints',
            'recentActivity',
            'user'
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
            ->with(['user', 'logs' => fn ($query) => $query
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

    public function complaintsIndex(Request $request): View
    {
        $user = Auth::user();
        $department = $user->department;
        $departmentId = $user->department_id;
        $status = $request->get('status');
        $reviewStatus = $request->get('review');
        $priority = $request->get('priority');
        $search = $request->get('search');
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

        $query = Complaint::where('department_id', $departmentId)->with(['user']);

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
                    ->where('ticket_id', 'ILIKE', '%' . $search . '%')
                    ->orWhere('title', 'ILIKE', '%' . $search . '%')
                    ->orWhere('description', 'ILIKE', '%' . $search . '%');
            });
        }

        $complaints = $query
            ->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE COALESCE(confirmed_priority, suggested_priority) WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 WHEN 'elevated' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'new' => Complaint::where('department_id', $departmentId)->where('review_status', ComplaintReviewStatus::Pending->value)->count(),
            'inProgress' => Complaint::where('department_id', $departmentId)->where('status', ComplaintStatus::InProgress->value)->count(),
            'resolvedThisMonth' => Complaint::where('department_id', $departmentId)
                ->where('status', ComplaintStatus::Resolved->value)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'avgDays' => $this->calculateAvgResolutionDays($departmentId),
        ];

        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

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
            'user'
        ));
    }

    /**
     * Show single complaint details for staff
     */
    public function show(Complaint $complaint): View
    {
        $user = Auth::user();
        
        // Authorization: staff can only view their department's complaints
        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            abort(403, 'This complaint is not assigned to your department.');
        }

        $complaint->load(['user', 'department', 'logs.actor', 'reviewedBy']);
        
        // Status options for dropdown (excluding current status to force change)
        $statuses = array_filter(ComplaintStatus::cases(), fn($s) => $s->value !== $complaint->statusEnum->value);
        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

        return view('staff.complaints.show', compact(
            'complaint',
            'statuses',
            'reviewStatusOptions',
            'priorityOptions',
            'user'
        ));
    }

    /**
     * Review complaint legitimacy and confirm the operational priority.
     */
    public function review(Request $request, Complaint $complaint, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            abort(403, 'This complaint is not assigned to your department.');
        }

        $validated = $request->validate([
            'review_status' => ['required', Rule::enum(ComplaintReviewStatus::class)],
            'confirmed_priority' => ['nullable', Rule::enum(ComplaintPriority::class)],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $reviewStatus = ComplaintReviewStatus::from($validated['review_status']);
        $confirmedPriority = isset($validated['confirmed_priority'])
            ? ComplaintPriority::from($validated['confirmed_priority'])
            : null;

        if ($reviewStatus === ComplaintReviewStatus::Verified && !$confirmedPriority) {
            return back()->with('error', 'A confirmed priority is required before verifying a complaint.');
        }

        $isPublic = array_key_exists('is_public', $validated)
            ? (bool) $validated['is_public']
            : $complaint->is_public;

        if ($reviewStatus !== ComplaintReviewStatus::Verified) {
            $confirmedPriority = null;
            $isPublic = false;
        }

        $oldReviewStatus = $complaint->reviewStatusEnum;
        $oldPriority = $complaint->confirmedPriorityEnum;
        $oldStatus = $complaint->statusEnum;
        $updates = [
            'review_status' => $reviewStatus->value,
            'confirmed_priority' => $confirmedPriority?->value,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? $complaint->review_notes,
            'is_public' => $isPublic,
        ];

        if ($reviewStatus === ComplaintReviewStatus::Verified && $oldStatus === ComplaintStatus::Submitted) {
            $updates['status'] = ComplaintStatus::UnderReview->value;
        }

        if ($reviewStatus === ComplaintReviewStatus::Rejected
            && in_array($oldStatus, [ComplaintStatus::Submitted, ComplaintStatus::UnderReview], true)) {
            $updates['status'] = ComplaintStatus::Rejected->value;
        }

        DB::transaction(function () use ($complaint, $updates, $oldStatus, $reviewStatus, $confirmedPriority, $oldReviewStatus, $oldPriority, $activityLogger): void {
            $complaint->update($updates);

            $priorityText = $confirmedPriority?->label() ?? 'Not confirmed';
            $comment = "Department review: {$reviewStatus->label()}; confirmed priority: {$priorityText}.";
            if (!empty($complaint->review_notes)) {
                $comment .= ' Review notes: ' . $complaint->review_notes;
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
                    'is_public' => $updates['is_public'],
                ],
            );
        });

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', "Complaint review saved as {$reviewStatus->label()}.");
    }

    /**
     * Update complaint status and add staff note (with optional internal note)
     */
    public function update(Request $request, Complaint $complaint, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();
        
        // Authorization check
        if ($user->role === 'staff' && $complaint->department_id !== $user->department_id) {
            return back()->with('error', 'You cannot update complaints outside your department.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['required', 'string', 'min:10', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $complaint->statusEnum;
        $newStatus = ComplaintStatus::from($validated['status']);

        if (!$complaint->isVerified() && $newStatus !== ComplaintStatus::Submitted) {
            return back()->with('error', 'This complaint must pass department review before its status can advance.');
        }

        // Prevent status changes on terminal states (Rejected, Closed)
        if ($oldStatus === ComplaintStatus::Rejected || $oldStatus === ComplaintStatus::Closed) {
            return back()->with('error', 'Cannot update a complaint that is already terminal (Rejected or Closed).');
        }

        // Enforce valid transitions using the enum's allowedTransitions
        if (!$oldStatus->canTransitionTo($newStatus)) {
            return back()->with('error', 'Invalid status transition from ' . $oldStatus->label() . ' to ' . $newStatus->label() . '.');
        }

        DB::transaction(function () use ($complaint, $oldStatus, $newStatus, $validated, $user, $activityLogger): void {
            $complaint->update([
                'status' => $newStatus->value,
            ]);

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'comment' => $validated['note'],
                'actor_id' => $user->id,
                'is_internal' => false,
            ]);

            $activityLogger->log(
                'complaint.status_updated',
                "Complaint {$complaint->ticket_id} moved from {$oldStatus->label()} to {$newStatus->label()}.",
                $complaint,
                metadata: [
                    'previous_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'public_note' => $validated['note'],
                ],
            );

            if (!empty($validated['internal_note'])) {
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

        // Send email notification to citizen
        Mail::to($complaint->user->email)->send(
            new ComplaintUpdatedMail($complaint, $newStatus->value, $validated['note'])
        );

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', 'Response sent and status updated successfully.');
    }
}
