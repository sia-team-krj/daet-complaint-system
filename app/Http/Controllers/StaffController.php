<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Mail\ComplaintUpdatedMail;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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
    public function dashboard(Request $request): View
    {
        $user = Auth::user();
        $department = $user->department;
        $departmentId = $user->department_id;
        $tab = $request->get('tab', 'queue');

        if ($tab === 'my-activity') {
            return $this->myActivityDashboard($request, $user, $department);
        }

        // TAB: Queue - Department-wide complaints
        $baseQuery = Complaint::where('department_id', $departmentId)
            ->with(['user', 'department']);

        // Stats
        $stats = [
            'new' => (clone $baseQuery)->where('status', ComplaintStatus::Submitted)->count(),
            'inProgress' => (clone $baseQuery)->where('status', ComplaintStatus::InProgress)->count(),
            'resolvedThisMonth' => (clone $baseQuery)
                ->where('status', ComplaintStatus::Resolved)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'avgDays' => $this->calculateAvgResolutionDays($departmentId),
        ];

        // Filter by status
        $status = $request->get('status');
        $search = $request->get('search');
        $complaintsQuery = Complaint::where('department_id', $departmentId)
            ->with(['user']);

        if ($status && in_array($status, ['Submitted', 'InProgress', 'Resolved', 'Rejected'])) {
            $complaintsQuery->where('status', $status);
        }

        if ($search) {
            $complaintsQuery->where(function($q) use ($search) {
                $q->where('ticket_id', 'ILIKE', '%' . $search . '%')
                  ->orWhere('title', 'ILIKE', '%' . $search . '%')
                  ->orWhere('description', 'ILIKE', '%' . $search . '%');
            });
        }

        // Sort: oldest unresolved first
        $complaints = $complaintsQuery
            ->orderByRaw("CASE WHEN status IN ('Submitted', 'In Progress') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('staff.dashboard.index', compact(
            'department',
            'stats',
            'complaints',
            'tab',
            'status',
            'search',
            'user'
        ));
    }

    /**
     * My Activity tab - complaints this staff member has handled
     */
    private function myActivityDashboard(Request $request, $user, $department): View
    {
        $departmentId = $user->department_id;

        // Get complaints where this staff has responded
        $handledComplaintIds = ComplaintLog::where('actor_id', $user->id)
            ->pluck('complaint_id')
            ->unique()
            ->values();

        // Personal stats
        $stats = [
            'totalResponded' => $handledComplaintIds->count(),
            'resolvedByMe' => ComplaintLog::where('actor_id', $user->id)
                ->where('new_status', 'Resolved')
                ->count(),
            'lastActivity' => ComplaintLog::where('actor_id', $user->id)
                ->latest('created_at')
                ->first()?->created_at,
        ];

        // Get complaints with my last response
        $complaints = Complaint::whereIn('id', $handledComplaintIds)
            ->with(['user'])
            ->with(['logs' => function($q) use ($user) {
                $q->where('actor_id', $user->id)
                  ->latest('created_at')
                  ->limit(1);
            }])
            ->orderByDesc(
                ComplaintLog::select('created_at')
                    ->whereColumn('complaint_logs.complaint_id', 'complaints.id')
                    ->where('complaint_logs.actor_id', $user->id)
                    ->latest()
                    ->limit(1)
            )
            ->paginate(10)
            ->withQueryString();

        $tab = 'my-activity';

        return view('staff.dashboard.index', compact(
            'department',
            'stats',
            'complaints',
            'tab',
            'user'
        ));
    }

    /**
     * Calculate average resolution days for department
     */
    private function calculateAvgResolutionDays(?int $departmentId): ?float
    {
        $query = Complaint::where('status', ComplaintStatus::Resolved);
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        $avgDays = $query
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days')
            ->first()
            ->avg_days;
        
        return $avgDays ? round($avgDays, 1) : null;
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

        $complaint->load(['user', 'department', 'logs.actor']);
        
        // Status options for dropdown (excluding current status to force change)
        $statuses = array_filter(ComplaintStatus::cases(), fn($s) => $s->value !== $complaint->status->value);

        return view('staff.complaints.show', compact(
            'complaint',
            'statuses',
            'user'
        ));
    }

    /**
     * Update complaint status and add staff note (with optional internal note)
     */
    public function update(Request $request, Complaint $complaint): RedirectResponse
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

        $oldStatus = $complaint->status;
        $newStatus = ComplaintStatus::from($validated['status']);

        // Prevent setting back to Draft
        if ($newStatus === ComplaintStatus::Draft) {
            return back()->with('error', 'Cannot revert complaint to Draft status.');
        }

        // Update complaint
        $complaint->update([
            'status' => $newStatus,
        ]);

        // Create public audit log entry
        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'previous_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'comment' => $validated['note'],
            'changed_by' => $user->id,
            'is_internal' => false,
        ]);

        // Create internal note if provided
        if (!empty($validated['internal_note'])) {
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => null,
                'new_status' => null,
                'comment' => $validated['internal_note'],
                'changed_by' => $user->id,
                'is_internal' => true,
            ]);
        }

        // Send email notification to citizen
        Mail::to($complaint->user->email)->send(
            new ComplaintUpdatedMail($complaint, $newStatus->value, $validated['note'])
        );

        return redirect()
            ->route('staff.complaints.show', $complaint)
            ->with('status', 'Response sent and status updated successfully.');
    }
}
