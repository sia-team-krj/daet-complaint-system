<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\VerificationMethod;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use App\Services\ActivityLogger;
use App\Services\ComplaintModerationService;
use App\Services\ComplaintVerificationService;
use App\Services\EmailVerificationService;
use App\Services\DepartmentRouter;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    /** Complaints shown per page in the admin queue. */
    public const QUEUE_PAGE_SIZE = 5;
    /**
     * Admin dashboard with system-wide overview
     */
    public function dashboard(): View
    {
        $stats = [
            'total' => Complaint::count(),
            'unassigned' => Complaint::whereNull('department_id')->count(),
            'inProgress' => Complaint::where('status', ComplaintStatus::InProgress->value)->count(),
            'resolvedToday' => Complaint::where('status', ComplaintStatus::Resolved->value)
                ->whereDate('updated_at', today())->count(),
            'avgDays' => $this->calculateAvgResolutionDays(),
        ];

        // Department breakdown
        $departments = Department::withCount([
            'complaints as pending_count' => fn ($q) => $q->where('status', ComplaintStatus::Submitted->value),
            'complaints as in_progress_count' => fn ($q) => $q->where('status', ComplaintStatus::InProgress->value),
        ])->withCount('staff')->get();

        // Recent complaints (all departments)
        $recentComplaints = Complaint::with(['user', 'department'])
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Unassigned complaints (highlighted)
        $unassignedComplaints = Complaint::with('user')
            ->whereNull('department_id')
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'departments',
            'recentComplaints',
            'unassignedComplaints'
        ));
    }

    private function calculateAvgResolutionDays(): ?float
    {
        $avg = Complaint::where('status', ComplaintStatus::Resolved->value)
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days')
            ->first()
            ->avg_days;

        return $avg ? round($avg, 1) : null;
    }

    public function departmentsIndex(Request $request): View
    {
        $showArchived = $request->boolean('archived');

        $departments = Department::query()
            ->withCount([
                'staff',
                'complaints as open_complaints_count' => fn ($query) => $query->whereIn('status', [
                    ComplaintStatus::Submitted->value,
                    ComplaintStatus::UnderReview->value,
                    ComplaintStatus::InProgress->value,
                ]),
                'invitationCodes as active_invitations_count' => fn ($query) => $query->where('is_active', true),
            ])
            // The two tabs are complementary, not a toggle. Both cases need a
            // predicate: with no predicate on the archived tab the query returns
            // every office, so the tab reads as "Archived" while showing the
            // active list — which is what it did before this was written down.
            ->where('is_active', ! $showArchived)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.departments.index', [
            'departments' => $departments,
            'showArchived' => $showArchived,
            'archivedCount' => Department::query()->where('is_active', false)->count(),
            'activeCount' => Department::query()->where('is_active', true)->count(),
            // Every office a new one could replace. Used by the create form to
            // warn that a code is already spoken for.
            'routableCodes' => DepartmentRouter::ROUTABLE_CODES,
        ]);
    }

    public function departmentCreate(): View
    {
        return view('admin.departments.create', [
            // Shown on the page where the code is actually chosen. Without it an
            // admin reasonably expects a new office to start receiving reports
            // by itself, and only five codes do.
            'routableCodes' => DepartmentRouter::ROUTABLE_CODES,
            'existingCodes' => Department::orderBy('code')->pluck('code'),
        ]);
    }

    public function departmentStore(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'unique:departments,code'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'office name',
            'code' => 'office code',
        ]);

        $department = DB::transaction(function () use ($validated, $activityLogger): Department {
            $department = Department::create([
                ...$validated,
                'is_active' => true,
            ]);

            $activityLogger->log(
                'department.created',
                "Department {$department->name} ({$department->code}) created.",
                $department,
                metadata: [
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'is_active' => true,
                ],
            );

            return $department;
        });

        return redirect()
            ->route('admin.departments.index')
            ->with('status', "{$department->name} created.");
    }

    public function departmentUpdate(Request $request, Department $department, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department->id)],
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('departments', 'code')->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'office name',
            'code' => 'office code',
        ]);

        // The router maps categories to offices by code, so a code is not a free
        // label — it is a live routing key. Renaming one that the router
        // resolves silently strands every complaint category pointing at it.
        // Refuse rather than accept a change the operator cannot see the effect of.
        if ($validated['code'] !== $department->code && in_array($department->code, DepartmentRouter::ROUTABLE_CODES, true)) {
            throw ValidationException::withMessages([
                'code' => "Office code {$department->code} routes complaint categories automatically and cannot be changed. "
                    .'Archive this office and create a replacement if the code itself has to change.',
            ]);
        }

        $before = $department->only(['name', 'code', 'description']);

        $department->update($validated);

        $activityLogger->log(
            'department.updated',
            "Department {$department->name} ({$department->code}) updated.",
            $department,
            metadata: [
                'before' => $before,
                'after' => $department->only(['name', 'code', 'description']),
            ],
        );

        return redirect()
            ->route('admin.departments.index')
            ->with('status', "{$department->name} updated.");
    }

    /**
     * Archive an office.
     *
     * Archive, never delete. Complaints, staff accounts, invitations, and audit
     * records all point at a department; deleting the row would null those
     * references and erase the routing history behind every case that office
     * ever handled. Archiving keeps the history readable and stops new routing,
     * which is reversible and loses nothing.
     *
     * Two guards, because both leave the system in a state no operator can
     * recover from without a database edit:
     *
     *  - The last active office cannot be archived. The router falls back to
     *    GSO, so with no active office every new complaint silently routes to
     *    nothing and lands in the unassigned pile.
     *  - An office with open complaints cannot be archived. Those cases still
     *    need an owner, and archiving would leave work stranded in an office
     *    that no longer appears in any assignment list.
     */
    public function departmentArchive(Department $department, ActivityLogger $activityLogger): RedirectResponse
    {
        if (! $department->is_active) {
            return back()->with('status', "{$department->name} is already archived.");
        }

        if (Department::query()->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'The last active department cannot be archived. Create another office first.');
        }

        $openComplaints = $department->complaints()
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])
            ->count();

        if ($openComplaints > 0) {
            return back()->with(
                'error',
                "{$department->name} still has {$openComplaints} open "
                    .($openComplaints === 1 ? 'complaint' : 'complaints')
                    .'. Resolve or reassign them before archiving.',
            );
        }

        $department->update(['is_active' => false]);

        $activityLogger->log(
            'department.archived',
            "Department {$department->name} ({$department->code}) archived. It no longer receives new complaints or staff invitations.",
            $department,
            metadata: ['previous_is_active' => true, 'is_active' => false],
        );

        return back()->with('status', "{$department->name} archived. Its complaint history is preserved.");
    }

    public function departmentRestore(Department $department, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($department->is_active) {
            return back()->with('status', "{$department->name} is already active.");
        }

        $department->update(['is_active' => true]);

        $activityLogger->log(
            'department.restored',
            "Department {$department->name} ({$department->code}) restored and available for routing again.",
            $department,
            metadata: ['previous_is_active' => false, 'is_active' => true],
        );

        return back()->with('status', "{$department->name} restored.");
    }

    public function auditIndex(Request $request): View
    {
        $departmentId = $request->filled('department_id') ? (int) $request->department_id : null;
        $actorId = $request->filled('actor_id') ? (int) $request->actor_id : null;

        // If a mismatched actor is supplied (e.g. URL tampering or stale query), ignore actor_id
        // instead of erroring; the actor filter is dependent on department in the UI.
        if ($departmentId && $actorId) {
            $actorMatchesDept = User::whereKey($actorId)
                ->where('department_id', $departmentId)
                ->exists();
            if (! $actorMatchesDept) {
                $actorId = null;
            }
        }

        $logs = ActivityLog::with(['actor', 'department', 'subject'])
            ->when($departmentId, fn ($query) => $query->forDepartment($departmentId))
            ->when($actorId, fn ($query) => $query->where('actor_id', $actorId))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->action))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('description', 'ILIKE', "%{$search}%")
                        ->orWhere('action', 'ILIKE', "%{$search}%")
                        ->orWhereHasMorph('subject', [Complaint::class], function ($complaintQuery) use ($search) {
                            $complaintQuery->where('ticket_id', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $actors = User::whereIn('role', ['staff', 'admin'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'role', 'department_id']);
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit.index', compact('logs', 'departments', 'actors', 'actions'));
    }

    /**
     * All complaints index with filters
     */
    public function complaintsIndex(Request $request): View
    {
        $review = $request->get('review');
        $priority = $request->get('priority');
        $query = Complaint::with(['user', 'department', 'assignedStaff']);

        $validReviewStatuses = array_map(
            fn (ComplaintReviewStatus $status): string => $status->value,
            ComplaintReviewStatus::cases(),
        );
        $validPriorities = array_map(
            fn (ComplaintPriority $status): string => $status->value,
            ComplaintPriority::cases(),
        );

        if ($review && in_array($review, $validReviewStatuses, true)) {
            $query->where('review_status', $review);
        }

        if ($priority && in_array($priority, $validPriorities, true)) {
            $query->where('confirmed_priority', $priority);
        }

        // Filters
        if ($request->filled('department')) {
            if ($request->department === 'unassigned') {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $request->department);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('ticket_id', 'ILIKE', '%' . $request->search . '%');
        }

        if ($request->boolean('flagged')) {
            $query->flagged();
        }

        // Five rows a page, as asked. A queue row is tall once it carries the
        // subject and provenance, and a short page is what makes the next/prev
        // controls usable on a phone rather than decorative.
        //
        // Held as a constant because the alternative is the number appearing in
        // three places — this call, the view's copy, and the test that pins it —
        // where changing one silently changes what the other two promise.
        $complaints = $query->latest()->paginate(self::QUEUE_PAGE_SIZE)->withQueryString();
        // Two lists, because this view needs two different things. The filter has
        // to include archived offices or a search over last year's work returns
        // nothing; the bulk-reassign control must not offer them, because routing
        // new work to an archived office is the thing archiving prevents.
        $departments = Department::orderBy('name')->get();
        $assignableDepartments = $this->assignableDepartments();
        $statuses = ComplaintStatus::cases();
        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

        return view('admin.complaints.index', compact(
            'complaints',
            'departments',
            'statuses',
            'reviewStatusOptions',
            'priorityOptions'
        ));
    }

    /**
     * Bulk reassign complaints to department
     */
    public function bulkReassign(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'complaint_ids' => ['required', 'array'],
            'complaint_ids.*' => ['exists:complaints,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $complaints = Complaint::whereIn('id', $validated['complaint_ids'])->get();
        $newDeptId = $validated['department_id'];
        $newDept = $newDeptId ? Department::find($newDeptId) : null;

        $updatedCount = DB::transaction(function () use ($complaints, $newDeptId, $newDept, $activityLogger): int {
            $updatedCount = 0;

            foreach ($complaints as $complaint) {
                $oldDepartmentId = $complaint->department_id;
                $oldDeptName = $complaint->department?->name ?? 'Unassigned';
                $newDeptName = $newDept?->name ?? 'Unassigned';

                if ((string) $oldDepartmentId === (string) $newDeptId) {
                    continue;
                }

                $complaint->update(['department_id' => $newDeptId]);

                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'previous_status' => $complaint->statusEnum->value,
                    'new_status' => $complaint->statusEnum->value,
                    'comment' => "Reassigned from {$oldDeptName} to {$newDeptName} by admin",
                    'actor_id' => Auth::id(),
                ]);

                $activityLogger->log(
                    'complaint.reassigned',
                    "Complaint {$complaint->ticket_id} reassigned from {$oldDeptName} to {$newDeptName}.",
                    $complaint,
                    departmentId: $newDeptId ? (int) $newDeptId : null,
                    metadata: [
                        'previous_department' => $oldDeptName,
                        'new_department' => $newDeptName,
                    ],
                );
                $updatedCount++;
            }

            return $updatedCount;
        });

        return back()->with('status', $updatedCount.' complaints reassigned successfully.');
    }

    /**
     * Export complaints to CSV
     */
    public function exportComplaints(Request $request)
    {
        $query = Complaint::with(['user', 'department']);

        // Apply same filters as index
        if ($request->filled('department')) {
            if ($request->department === 'unassigned') {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $request->department);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('ticket_id', 'ILIKE', '%' . $request->search . '%');
        }

        $complaints = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="complaints-export.csv"',
        ];

        $callback = function () use ($complaints) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Ticket ID', 'Citizen Name', 'Email', 'Department', 'Category', 'Status', 'Suggested Priority', 'Confirmed Priority', 'Review Status', 'Date Filed', 'Date Resolved']);

            foreach ($complaints as $c) {
                fputcsv($file, [
                    $c->ticket_id,
                    $c->user?->full_name ?? 'Unknown resident',
                    $c->user?->email ?? '',
                    $c->department?->name ?? 'Unassigned',
                    $c->category,
                    $c->statusEnum->label(),
                    $c->suggestedPriorityEnum->label(),
                    $c->confirmedPriorityEnum?->label() ?? 'Not confirmed',
                    $c->reviewStatusEnum->label(),
                    $c->created_at->format('Y-m-d'),
                    $c->statusEnum === ComplaintStatus::Resolved ? $c->updated_at->format('Y-m-d') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show single complaint details (admin view)
     */
    public function complaintShow(Complaint $complaint): View
    {
        $complaint->load(['user', 'department', 'logs.actor', 'assignedStaff', 'reviewedBy']);

        $statuses = ComplaintStatus::cases();
        // Passes the complaint's current office so an archived one still shows
        // as the selected value instead of the select silently falling through
        // to its first option and displaying the wrong department.
        $departments = $this->assignableDepartments($complaint->department);
        $staffMembers = User::where('role', 'staff')
            ->with('department')
            ->orderBy('first_name')
            ->get();
        $reviewStatusOptions = ComplaintReviewStatus::cases();
        $priorityOptions = ComplaintPriority::cases();

        return view('admin.complaints.show', compact(
            'complaint',
            'statuses',
            'departments',
            'staffMembers',
            'reviewStatusOptions',
            'priorityOptions'
        ));
    }

    /**
     * Update complaint status, department, or staff assignment
     */
    public function complaintUpdate(Request $request, Complaint $complaint, ActivityLogger $activityLogger, ComplaintVerificationService $verification, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ComplaintStatus::class)],
            'review_status' => ['nullable', Rule::enum(ComplaintReviewStatus::class)],
            'confirmed_priority' => ['nullable', Rule::enum(ComplaintPriority::class)],
            'verification_method' => ['nullable', Rule::enum(VerificationMethod::class)],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            // is_public is deliberately absent: public visibility is the
            // resident's decision, not an administrative one. Admins moderate
            // content through the separate moderation action.
            'staff_note' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assigned_staff_id' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $complaint->statusEnum;
        $oldDeptId = $complaint->department_id;
        $oldReviewStatus = $complaint->reviewStatusEnum;
        $oldPriority = $complaint->confirmedPriorityEnum;
        $oldAssignedStaffId = $complaint->assigned_staff_id;
        $assignedStaff = null;
        $updates = [];
        $logEntries = [];

        $reviewInputProvided = array_key_exists('review_status', $validated)
            || array_key_exists('confirmed_priority', $validated)
            || array_key_exists('review_notes', $validated)
            || array_key_exists('verification_method', $validated);

        if ($reviewInputProvided) {
            $reviewStatus = isset($validated['review_status'])
                ? ComplaintReviewStatus::from($validated['review_status'])
                : $oldReviewStatus;
            $confirmedPriority = isset($validated['confirmed_priority'])
                ? ComplaintPriority::from($validated['confirmed_priority'])
                : $oldPriority;
            $method = isset($validated['verification_method'])
                ? VerificationMethod::from($validated['verification_method'])
                : $complaint->verificationMethodEnum();

            // The rules live in one place so the admin path and the department
            // verifier path cannot drift. Admin is always allowed to verify,
            // which is the fallback when a department's verifier is unavailable.
            try {
                $updates += $verification->reviewUpdates(
                    $complaint,
                    Auth::user(),
                    $reviewStatus,
                    $confirmedPriority,
                    $method,
                    array_key_exists('review_notes', $validated) ? $validated['review_notes'] : null,
                );
            } catch (ValidationException $exception) {
                return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
            }
        }

        // Handle department reassignment
        if (array_key_exists('department_id', $validated) && $validated['department_id'] != $oldDeptId) {
            $updates['department_id'] = $validated['department_id'];
            $oldDept = $oldDeptId ? Department::find($oldDeptId)?->name : 'Unassigned';
            $newDept = $validated['department_id'] ? Department::find($validated['department_id'])?->name : 'Unassigned';
            $updates['assigned_staff_id'] = null;
            $logEntries[] = "Reassigned from {$oldDept} to {$newDept}";
        }

        // Handle staff assignment
        if (array_key_exists('assigned_staff_id', $validated)) {
            $staff = $validated['assigned_staff_id']
                ? User::find($validated['assigned_staff_id'])
                : null;
            $targetDepartmentId = (int) ($validated['department_id'] ?? $complaint->department_id);

            if ($staff && $staff->role === 'staff' && (int) $staff->department_id === $targetDepartmentId) {
                $updates['assigned_staff_id'] = $staff->id;
                $assignedStaff = $staff;
                $logEntries[] = "Assigned to staff: {$staff->full_name}";
            }
        }

        // Handle status change
        if (isset($validated['status'])) {
            $newStatus = ComplaintStatus::from($validated['status']);
            if (! $complaint->isVerified() && $newStatus !== ComplaintStatus::Submitted) {
                return back()->with('error', 'This complaint must pass review before its status can advance.');
            }
            if ($newStatus !== $oldStatus) {
                $updates['status'] = $newStatus->value;
            }
        }

        // Apply updates
        if (! empty($updates)) {
            $complaint->update($updates);
        }

        // Create log entry
        $note = $validated['staff_note'] ?? null;
        if (! empty($logEntries)) {
            $note = ($note ? $note."\n" : '').implode('; ', $logEntries);
        }

        if (isset($updates['status']) || $note) {
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $oldStatus->value,
                'new_status' => $updates['status'] ?? $oldStatus->value,
                'comment' => $note,
                'actor_id' => Auth::id(),
            ]);
        }

        if ($reviewInputProvided) {
            $newReviewStatus = ComplaintReviewStatus::from($updates['review_status'] ?? $oldReviewStatus->value);
            $newPriority = isset($updates['confirmed_priority'])
                ? ComplaintPriority::from($updates['confirmed_priority'])
                : null;
            $reviewComment = "Administrative review: {$newReviewStatus->label()}; confirmed priority: ".($newPriority?->label() ?? 'Not confirmed').'.';
            if (! empty($updates['review_notes'])) {
                $reviewComment .= ' Review notes: '.$updates['review_notes'];
            }

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $oldStatus->value,
                'new_status' => $updates['status'] ?? $oldStatus->value,
                'comment' => $reviewComment,
                'actor_id' => Auth::id(),
            ]);

            $activityLogger->log(
                'complaint.reviewed',
                "Complaint {$complaint->ticket_id} moved from {$oldReviewStatus->label()} to {$newReviewStatus->label()}.",
                $complaint,
                metadata: [
                    'previous_review_status' => $oldReviewStatus->value,
                    'review_status' => $newReviewStatus->value,
                    'previous_confirmed_priority' => $oldPriority?->value,
                    'confirmed_priority' => $newPriority?->value,
                ],
            );
        }

        if (array_key_exists('department_id', $updates)) {
            $oldDepartment = $oldDeptId ? Department::find($oldDeptId)?->name : 'Unassigned';
            $newDepartment = $validated['department_id'] ? Department::find($validated['department_id'])?->name : 'Unassigned';

            $activityLogger->log(
                'complaint.reassigned',
                "Complaint {$complaint->ticket_id} reassigned from {$oldDepartment} to {$newDepartment}.",
                $complaint,
                departmentId: $validated['department_id'] ? (int) $validated['department_id'] : null,
                metadata: [
                    'previous_department' => $oldDepartment,
                    'new_department' => $newDepartment,
                ],
            );
        }

        if (array_key_exists('assigned_staff_id', $updates) && array_key_exists('assigned_staff_id', $validated)) {
            $assignedStaff = $updates['assigned_staff_id'] ? User::find($updates['assigned_staff_id']) : null;
            $activityLogger->log(
                'complaint.assignment_updated',
                $assignedStaff
                    ? "Complaint {$complaint->ticket_id} assigned to {$assignedStaff->full_name}."
                    : "Staff assignment cleared for complaint {$complaint->ticket_id}.",
                $complaint,
                metadata: ['assigned_staff_id' => $updates['assigned_staff_id']],
            );
        }

        if (isset($updates['status'])) {
            $activityLogger->log(
                'complaint.status_updated',
                "Complaint {$complaint->ticket_id} moved from {$oldStatus->label()} to ".ComplaintStatus::from($updates['status'])->label().'.',
                $complaint,
                metadata: [
                    'previous_status' => $oldStatus->value,
                    'new_status' => $updates['status'],
                ],
            );
        }

        if (! empty($validated['staff_note'])) {
            $activityLogger->log(
                'complaint.note_added',
                "Administrative note added to complaint {$complaint->ticket_id}.",
                $complaint,
                metadata: ['note' => $validated['staff_note']],
            );
        }

        // Tell the people the update is about. A review decision takes
        // precedence over the status transition it may cause, so one admin
        // action cannot produce two notifications for the same thing.
        try {
            $reviewChanged = $reviewInputProvided
                && isset($updates['review_status'])
                && (string) $updates['review_status'] !== $oldReviewStatus->value;

            $reviewNotification = null;

            if ($reviewChanged) {
                $reviewNotification = $notifications->reviewUpdated(
                    $complaint,
                    ComplaintReviewStatus::from($updates['review_status']),
                    Auth::user(),
                );
            }

            $statusChanged = isset($updates['status'])
                && (string) $updates['status'] !== $oldStatus->value;

            if ($statusChanged && $reviewNotification === null) {
                $notifications->statusChanged(
                    $complaint,
                    ComplaintStatus::from($updates['status'])->label(),
                    Auth::user(),
                );
            }

            if ($assignedStaff !== null && (int) $assignedStaff->id !== (int) $oldAssignedStaffId) {
                $notifications->complaintAssigned($complaint, $assignedStaff, Auth::user());
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Complaint updated successfully.');
    }

    /**
     * Withhold or redact a complaint's public presentation.
     *
     * An admin has more reach than a reviewer, not more authority over
     * consent. This action can stop inappropriate content being broadcast, and
     * cannot publish or unpublish anything on a resident's behalf.
     */
    public function moderateComplaint(
        Request $request,
        Complaint $complaint,
        ComplaintModerationService $moderation,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
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
            $updates = $moderation->apply($complaint, Auth::user(), $validated);
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
                'resident_visibility_untouched' => true,
            ],
        );

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Moderation saved. The resident still sees their complaint exactly as they filed it.');
    }

    /**
     * Offices that may be assigned work right now.
     *
     * Active offices, plus `$current` when it is archived. The archived one is
     * included only so a control that already shows it keeps showing the truth:
     * without it a select whose selected value is missing falls through to its
     * first option, and the screen would report a department the record is not
     * actually in. It is not offered for new assignments because the router
     * skips archived offices and routing to one is what archiving prevents.
     */
    private function assignableDepartments(?Department $current = null): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->when($current !== null && ! $current->is_active, fn ($query) => $query->orWhereKey($current->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Staff accounts index
     */
    public function staffIndex(): View
    {
        $staff = User::where('role', 'staff')
            ->with('department')
            ->withCount('assignedComplaints')
            ->get();

        // Every office a staff member currently belongs to is included even when
        // archived, for the same reason the complaint view does it: the select
        // has to show the account's real department, not fall through to the
        // first option and quietly misreport where that person works.
        $departments = Department::orderBy('name')->get()
            ->filter(fn (Department $department) => $department->is_active
                || $staff->contains(fn (User $member) => $member->department_id === $department->id))
            ->values();

        return view('admin.staff.index', compact('staff', 'departments'));
    }

    /**
     * Show create staff form
     */
    public function staffCreate(): View
    {
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.staff.create', compact('departments'));
    }

    /**
     * Store new staff account
     */
    public function staffStore(
        Request $request,
        ActivityLogger $activityLogger,
        EmailVerificationService $verificationService,
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'department_id' => ['required', 'exists:departments,id'],
            'contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $tempPassword = Str::random(12);

        $staff = DB::transaction(function () use ($validated, $tempPassword, $activityLogger): User {
            $staff = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'] ?? null,
                'department_id' => $validated['department_id'],
                'created_by' => Auth::id(),
                'role' => 'staff',
                'is_active' => true,
                'password' => Hash::make($tempPassword),
            ]);

            $activityLogger->log(
                'staff.created',
                "Staff account created for {$staff->full_name}.",
                $staff,
                departmentId: (int) $validated['department_id'],
            );

            return $staff;
        });

        // Send welcome email and the first email-verification OTP.
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
            ->route('admin.staff.index')
            ->with(
                'status',
                $otpSent
                    ? 'Staff account created. Welcome email and verification code sent.'
                    : 'Staff account created. Welcome email sent, but the verification code could not be sent.',
            );
    }

    /**
     * Toggle staff account active status
     */
    public function staffToggleStatus(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($user->role !== 'staff') {
            return back()->with('error', 'Can only toggle staff accounts.');
        }

        $wasActive = (bool) $user->is_active;
        $user->update(['is_active' => ! $wasActive]);

        $activityLogger->log(
            $wasActive ? 'staff.deactivated' : 'staff.activated',
            "Staff account {$user->full_name} ".($wasActive ? 'deactivated' : 'activated').'.',
            $user,
            departmentId: $user->department_id,
            metadata: ['previous_is_active' => $wasActive, 'is_active' => ! $wasActive],
        );

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "Staff account {$status} successfully.");
    }

    /**
     * Grant or revoke a staff member's ability to settle complaints.
     *
     * Verification is a capability rather than a role, so it needs a control of
     * its own here. Without it, an existing deployment where every staff account
     * defaults to can_verify = false has nobody who can review anything.
     */
    public function staffToggleVerification(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($user->role !== 'staff') {
            return back()->with('error', 'Can only change verification rights on staff accounts.');
        }

        $wasVerifier = (bool) $user->can_verify;
        $user->update(['can_verify' => ! $wasVerifier]);

        $activityLogger->log(
            $wasVerifier ? 'staff.verification_revoked' : 'staff.verification_granted',
            "Verification rights ".($wasVerifier ? 'revoked from' : 'granted to')." {$user->full_name}.",
            $user,
            departmentId: $user->department_id,
            metadata: ['previous_can_verify' => $wasVerifier, 'can_verify' => ! $wasVerifier],
        );

        return back()->with(
            'status',
            $user->fresh()->can_verify
                ? "{$user->full_name} can now settle complaints for their department."
                : "{$user->full_name} can no longer settle complaints.",
        );
    }

    /**
     * Update staff department assignment
     */
    public function staffUpdateDepartment(Request $request, User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($user->role !== 'staff') {
            return back()->with('error', 'Can only update staff accounts.');
        }

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $oldDepartmentId = $user->department_id;
        $oldDepartment = $oldDepartmentId ? Department::find($oldDepartmentId)?->name : 'Unassigned';
        $newDepartment = Department::find($validated['department_id'])?->name ?? 'Unassigned';

        $user->update(['department_id' => $validated['department_id']]);

        $activityLogger->log(
            'staff.department_updated',
            "{$user->full_name} moved from {$oldDepartment} to {$newDepartment}.",
            $user,
            departmentId: (int) $validated['department_id'],
            metadata: [
                'previous_department' => $oldDepartment,
                'new_department' => $newDepartment,
            ],
        );

        return back()->with('status', 'Staff department updated.');
    }
}
