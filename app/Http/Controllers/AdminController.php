<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
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
            'complaints as pending_count' => fn($q) => $q->where('status', ComplaintStatus::Submitted->value),
            'complaints as in_progress_count' => fn($q) => $q->where('status', ComplaintStatus::InProgress->value),
        ])->withCount('staff')->get();

        // Recent complaints (all departments)
        $recentComplaints = Complaint::with(['user', 'department'])
            ->latest()
            ->limit(10)
            ->get();

        // Unassigned complaints (highlighted)
        $unassignedComplaints = Complaint::with('user')
            ->whereNull('department_id')
            ->latest()
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

    public function departmentsIndex(): View
    {
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
            ->orderBy('name')
            ->paginate(12);

        return view('admin.departments.index', compact('departments'));
    }

    public function departmentStore(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'unique:departments,code'],
            'description' => ['nullable', 'string', 'max:255'],
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
            );

            return $department;
        });

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Department created.');
    }

    public function auditIndex(Request $request): View
    {
        $logs = ActivityLog::with(['actor', 'department', 'subject'])
            ->when($request->filled('department_id'), fn ($query) => $query->forDepartment((int) $request->department_id))
            ->when($request->filled('actor_id'), fn ($query) => $query->where('actor_id', $request->actor_id))
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

        $complaints = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::all();
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

        return back()->with('status', $updatedCount . ' complaints reassigned successfully.');
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
        $departments = Department::all();
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
    public function complaintUpdate(Request $request, Complaint $complaint, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ComplaintStatus::class)],
            'review_status' => ['nullable', Rule::enum(ComplaintReviewStatus::class)],
            'confirmed_priority' => ['nullable', Rule::enum(ComplaintPriority::class)],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['nullable', 'boolean'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assigned_staff_id' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $complaint->statusEnum;
        $oldDeptId = $complaint->department_id;
        $oldReviewStatus = $complaint->reviewStatusEnum;
        $oldPriority = $complaint->confirmedPriorityEnum;
        $updates = [];
        $logEntries = [];

        $reviewInputProvided = array_key_exists('review_status', $validated)
            || array_key_exists('confirmed_priority', $validated)
            || array_key_exists('review_notes', $validated)
            || array_key_exists('is_public', $validated);

        if ($reviewInputProvided) {
            $reviewStatus = isset($validated['review_status'])
                ? ComplaintReviewStatus::from($validated['review_status'])
                : $oldReviewStatus;
            $confirmedPriority = isset($validated['confirmed_priority'])
                ? ComplaintPriority::from($validated['confirmed_priority'])
                : $oldPriority;
            $requestedPublic = array_key_exists('is_public', $validated)
                ? (bool) $validated['is_public']
                : $complaint->is_public;
            $isPublic = $requestedPublic;

            if ($reviewStatus === ComplaintReviewStatus::Verified && !$confirmedPriority) {
                return back()->with('error', 'A confirmed priority is required before verifying a complaint.');
            }

            if ($requestedPublic && $reviewStatus !== ComplaintReviewStatus::Verified) {
                return back()->with('error', 'Only verified complaints can be marked public.');
            }

            if ($reviewStatus !== ComplaintReviewStatus::Verified) {
                $confirmedPriority = null;
                $isPublic = false;
            }

            $updates['review_status'] = $reviewStatus->value;
            $updates['confirmed_priority'] = $confirmedPriority?->value;
            $updates['reviewed_by'] = Auth::id();
            $updates['reviewed_at'] = now();
            $updates['is_public'] = $isPublic;

            if (array_key_exists('review_notes', $validated)) {
                $updates['review_notes'] = $validated['review_notes'];
            }

            if ($reviewStatus === ComplaintReviewStatus::Verified && $oldStatus === ComplaintStatus::Submitted) {
                $updates['status'] = ComplaintStatus::UnderReview->value;
            }

            if ($reviewStatus === ComplaintReviewStatus::Rejected
                && in_array($oldStatus, [ComplaintStatus::Submitted, ComplaintStatus::UnderReview], true)) {
                $updates['status'] = ComplaintStatus::Rejected->value;
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
                $logEntries[] = "Assigned to staff: {$staff->full_name}";
            }
        }

        // Handle status change
        if (isset($validated['status'])) {
            $newStatus = ComplaintStatus::from($validated['status']);
            if (!$complaint->isVerified() && $newStatus !== ComplaintStatus::Submitted) {
                return back()->with('error', 'This complaint must pass review before its status can advance.');
            }
            if ($newStatus !== $oldStatus) {
                $updates['status'] = $newStatus->value;
            }
        }

        // Apply updates
        if (!empty($updates)) {
            $complaint->update($updates);
        }

        // Create log entry
        $note = $validated['staff_note'] ?? null;
        if (!empty($logEntries)) {
            $note = ($note ? $note . "\n" : "") . implode("; ", $logEntries);
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
            $reviewComment = "Administrative review: {$newReviewStatus->label()}; confirmed priority: " . ($newPriority?->label() ?? 'Not confirmed') . '.';
            if (!empty($updates['review_notes'])) {
                $reviewComment .= ' Review notes: ' . $updates['review_notes'];
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
                    'is_public' => $updates['is_public'] ?? $complaint->is_public,
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
                "Complaint {$complaint->ticket_id} moved from {$oldStatus->label()} to " . ComplaintStatus::from($updates['status'])->label() . '.',
                $complaint,
                metadata: [
                    'previous_status' => $oldStatus->value,
                    'new_status' => $updates['status'],
                ],
            );
        }

        if (!empty($validated['staff_note'])) {
            $activityLogger->log(
                'complaint.note_added',
                "Administrative note added to complaint {$complaint->ticket_id}.",
                $complaint,
                metadata: ['note' => $validated['staff_note']],
            );
        }

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Complaint updated successfully.');
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

        $departments = Department::all();

        return view('admin.staff.index', compact('staff', 'departments'));
    }

    /**
     * Show create staff form
     */
    public function staffCreate(): View
    {
        $departments = Department::all();
        return view('admin.staff.create', compact('departments'));
    }

    /**
     * Store new staff account
     */
    public function staffStore(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
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

        // Send welcome email
        $staff->notify(new StaffWelcomeNotification($tempPassword));

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created. Welcome email sent with temporary password.');
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
        $user->update(['is_active' => !$wasActive]);

        $activityLogger->log(
            $wasActive ? 'staff.deactivated' : 'staff.activated',
            "Staff account {$user->full_name} " . ($wasActive ? 'deactivated' : 'activated') . '.',
            $user,
            departmentId: $user->department_id,
            metadata: ['previous_is_active' => $wasActive, 'is_active' => !$wasActive],
        );

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('status', "Staff account {$status} successfully.");
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
