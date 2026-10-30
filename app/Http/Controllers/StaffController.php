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
use App\Services\ActivityLogger;
use App\Services\ComplaintModerationService;
use App\Services\ComplaintVerificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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

        // Outstanding asks are eager-loaded, otherwise the queue would issue one
        // extra query per row just to show how long a resident has been waiting.
        $query = Complaint::where('department_id', $departmentId)->with([
            'user',
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

        $complaints = $query
            ->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE COALESCE(confirmed_priority, suggested_priority) WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 WHEN 'elevated' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->paginate(5)
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

        $complaint->load(['user', 'department', 'logs.actor', 'reviewedBy', 'verifyingStaff', 'informationRequests.requester']);

        // Status options for dropdown (excluding current status to force change)
        $statuses = array_filter(ComplaintStatus::cases(), fn ($s) => $s->value !== $complaint->statusEnum->value);
        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

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

        return view('staff.complaints.show', compact(
            'complaint',
            'statuses',
            'reviewStatusOptions',
            'priorityOptions',
            'departmentVerifiers',
            'user'
        ));
    }

    /**
     * Review complaint legitimacy and confirm the operational priority.
     */
    public function review(Request $request, Complaint $complaint, ActivityLogger $activityLogger, ComplaintVerificationService $verification): RedirectResponse
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
     * Processing and resolution updates require documentation photos as proof.
     */
    public function update(Request $request, Complaint $complaint, ActivityLogger $activityLogger, NotificationService $notifications): RedirectResponse
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

        $documentationPhotos = $this->documentationPhotos($request);
        $documentationRequired = in_array($newStatus, [
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
        ], true);

        if ($documentationRequired && $documentationPhotos === []) {
            return back()->withErrors([
                'documentation_images' => 'Attach at least one documentation photo before marking this complaint as '.strtolower($newStatus->label()).'.',
            ]);
        }

        if (count($documentationPhotos) > 5) {
            return back()->withErrors([
                'documentation_images' => 'You can attach a maximum of five documentation photos.',
            ]);
        }

        $documentationPaths = [];
        if ($documentationPhotos !== []) {
            try {
                foreach ($documentationPhotos as $photo) {
                    $path = $photo->store("complaint-updates/{$complaint->id}", 'minio');

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('The documentation photo could not be stored.');
                    }

                    $documentationPaths[] = $path;
                }
            } catch (Throwable $exception) {
                Storage::disk('minio')->delete($documentationPaths);
                report($exception);

                return back()->withErrors([
                    'documentation_images' => 'We could not save the documentation photos. Please try again.',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($complaint, $oldStatus, $newStatus, $validated, $user, $activityLogger, $documentationPaths): void {
                $complaint->update([
                    'status' => $newStatus->value,
                ]);

                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'previous_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                    'comment' => $validated['note'],
                    'documentation_paths' => $documentationPaths !== [] ? $documentationPaths : null,
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
                        'documentation_count' => count($documentationPaths),
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

        // Send email notification to citizen
        Mail::to($complaint->user->email)->send(
            new ComplaintUpdatedMail($complaint, $newStatus->value, $validated['note'])
        );

        // And the in-app notification, so the resident learns of the change
        // without having to remember to open the page. Reported rather than
        // thrown: a failed notification must not undo a status change the
        // department has already committed to, with its proof photos attached.
        try {
            $notifications->statusChanged($complaint, $newStatus->label(), $user);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', 'Response sent and status updated successfully.');
    }

    /**
     * Normalize gallery and camera documentation uploads.
     *
     * @return array<int, UploadedFile>
     */
    private function documentationPhotos(Request $request): array
    {
        $photos = $request->file('documentation_images', []);

        if ($photos instanceof UploadedFile) {
            $photos = [$photos];
        } elseif (! is_array($photos)) {
            $photos = [];
        }

        $cameraPhotos = $request->file('documentation_camera_photos', []);
        if ($cameraPhotos instanceof UploadedFile) {
            $cameraPhotos = [$cameraPhotos];
        } elseif (! is_array($cameraPhotos)) {
            $cameraPhotos = [];
        }
        $photos = array_merge($photos, $cameraPhotos);

        $legacyCameraPhoto = $request->file('documentation_camera_photo');
        if ($legacyCameraPhoto) {
            $photos[] = $legacyCameraPhoto;
        }

        return array_values(array_filter(
            $photos,
            fn (mixed $photo): bool => $photo instanceof UploadedFile && $photo->isValid(),
        ));
    }
}
