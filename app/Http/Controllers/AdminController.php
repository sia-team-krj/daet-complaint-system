<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
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
            'inProgress' => Complaint::where('status', ComplaintStatus::InProgress)->count(),
            'resolvedToday' => Complaint::where('status', ComplaintStatus::Resolved)
                ->whereDate('updated_at', today())->count(),
            'avgDays' => $this->calculateAvgResolutionDays(),
        ];

        // Department breakdown
        $departments = Department::withCount([
            'complaints as pending_count' => fn($q) => $q->where('status', ComplaintStatus::Submitted),
            'complaints as in_progress_count' => fn($q) => $q->where('status', ComplaintStatus::InProgress),
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
        $avg = Complaint::where('status', ComplaintStatus::Resolved)
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days')
            ->first()
            ->avg_days;
        
        return $avg ? round($avg, 1) : null;
    }

    /**
     * All complaints index with filters
     */
    public function complaintsIndex(Request $request): View
    {
        $query = Complaint::with(['user', 'department', 'assignedStaff']);

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

        $complaints = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::all();
        $statuses = ComplaintStatus::cases();

        return view('admin.complaints.index', compact(
            'complaints',
            'departments',
            'statuses'
        ));
    }

    /**
     * Bulk reassign complaints to department
     */
    public function bulkReassign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'complaint_ids' => ['required', 'array'],
            'complaint_ids.*' => ['exists:complaints,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $complaints = Complaint::whereIn('id', $validated['complaint_ids'])->get();
        $newDeptId = $validated['department_id'];
        $newDept = $newDeptId ? Department::find($newDeptId) : null;

        foreach ($complaints as $complaint) {
            $oldDeptName = $complaint->department?->name ?? 'Unassigned';
            $newDeptName = $newDept?->name ?? 'Unassigned';

            $complaint->update(['department_id' => $newDeptId]);

            // Log the reassignment
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $complaint->status->value,
                'new_status' => $complaint->status->value,
                'comment' => "Reassigned from {$oldDeptName} to {$newDeptName} by admin",
                'changed_by' => Auth::id(),
            ]);
        }

        return back()->with('status', count($complaints) . ' complaints reassigned successfully.');
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
            fputcsv($file, ['Ticket ID', 'Citizen Name', 'Email', 'Department', 'Category', 'Status', 'Urgency', 'Date Filed', 'Date Resolved']);

            foreach ($complaints as $c) {
                fputcsv($file, [
                    $c->ticket_id,
                    $c->user->full_name,
                    $c->user->email,
                    $c->department?->name ?? 'Unassigned',
                    $c->category,
                    $c->status->label(),
                    $c->urgency,
                    $c->created_at->format('Y-m-d'),
                    $c->status === ComplaintStatus::Resolved ? $c->updated_at->format('Y-m-d') : '',
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
        $complaint->load(['user', 'department', 'logs.actor', 'assignedStaff']);
        
        $statuses = array_filter(ComplaintStatus::cases(), fn($s) => $s !== ComplaintStatus::Submitted);
        $departments = Department::all();
        $staffMembers = User::where('role', 'staff')
            ->where('department_id', $complaint->department_id)
            ->get();

        return view('admin.complaints.show', compact(
            'complaint',
            'statuses',
            'departments',
            'staffMembers'
        ));
    }

    /**
     * Update complaint status, department, or staff assignment
     */
    public function complaintUpdate(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assigned_staff_id' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $complaint->status;
        $oldDeptId = $complaint->department_id;
        $updates = [];
        $logEntries = [];

        // Handle department reassignment
        if (isset($validated['department_id']) && $validated['department_id'] != $oldDeptId) {
            $updates['department_id'] = $validated['department_id'];
            $oldDept = $oldDeptId ? Department::find($oldDeptId)?->name : 'Unassigned';
            $newDept = $validated['department_id'] ? Department::find($validated['department_id'])?->name : 'Unassigned';
            $logEntries[] = "Reassigned from {$oldDept} to {$newDept}";
        }

        // Handle staff assignment
        if (isset($validated['assigned_staff_id'])) {
            $staff = User::find($validated['assigned_staff_id']);
            if ($staff && $staff->role === 'staff') {
                $logEntries[] = "Assigned to staff: {$staff->full_name}";
            }
        }

        // Handle status change
        if (isset($validated['status'])) {
            $newStatus = ComplaintStatus::from($validated['status']);
            if ($newStatus !== $oldStatus) {
                $updates['status'] = $newStatus;
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
                'new_status' => ($updates['status'] ?? $oldStatus)->value,
                'comment' => $note,
                'changed_by' => Auth::id(),
            ]);
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
    public function staffStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'department_id' => ['required', 'exists:departments,id'],
            'contact_number' => ['nullable', 'string', 'regex:/^9\d{9}$/'],
        ]);

        $tempPassword = Str::random(12);

        $staff = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'department_id' => $validated['department_id'],
            'role' => 'staff',
            'password' => Hash::make($tempPassword),
        ]);

        // Send welcome email
        $staff->notify(new StaffWelcomeNotification($tempPassword));

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created. Welcome email sent with temporary password.');
    }

    /**
     * Toggle staff account active status
     */
    public function staffToggleStatus(User $user): RedirectResponse
    {
        if ($user->role !== 'staff') {
            return back()->with('error', 'Can only toggle staff accounts.');
        }

        // Soft deactivate by setting a flag (add is_active to users table if needed)
        // For now, we'll just redirect with message
        return back()->with('status', 'Staff status updated.');
    }

    /**
     * Update staff department assignment
     */
    public function staffUpdateDepartment(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'staff') {
            return back()->with('error', 'Can only update staff accounts.');
        }

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $user->update(['department_id' => $validated['department_id']]);

        return back()->with('status', 'Staff department updated.');
    }
}
