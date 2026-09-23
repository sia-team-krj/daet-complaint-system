<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Paginated list of this citizen's complaints — latest first
        $complaints = Complaint::where('user_id', $user->id)
                               ->with('department')
                               ->latest()
                               ->paginate(10);

        // Per-citizen stats
        $totalComplaints    = Complaint::where('user_id', $user->id)->count();
        $resolvedComplaints = Complaint::where('user_id', $user->id)
                                       ->where('status', ComplaintStatus::Resolved->value)
                                       ->count();
        $pendingComplaints  = Complaint::where('user_id', $user->id)
                                       ->whereNotIn('status', [
                                           ComplaintStatus::Resolved->value,
                                           ComplaintStatus::Closed->value,
                                           ComplaintStatus::Rejected->value,
                                       ])
                                       ->count();

        // Average days to resolve (only resolved complaints)
        $avgDays = Complaint::where('user_id', $user->id)
                            ->where('status', ComplaintStatus::Resolved->value)
                            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days')
                            ->value('avg_days');

        // Round to nearest day or show dash if no resolved complaints
        $avgDays = $avgDays ? round($avgDays) : '—';

        // Package stats for view
        $stats = [
            'total'     => $totalComplaints,
            'pending'   => $pendingComplaints,
            'resolved'  => $resolvedComplaints,
            'avgDays'   => $avgDays,
        ];

        return view('dashboard.index', compact(
            'complaints',
            'stats'
        ));
    }
}