<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;

class HomeController extends Controller
{
    public function index()
    {
        // Authenticated users always enter the workspace for their role.
        // This prevents the public home controller from showing system-wide
        // complaint totals inside a personal dashboard.
        if (auth()->check()) {
            $route = match (auth()->user()->role) {
                'admin' => 'admin.dashboard',
                'staff' => 'staff.dashboard',
                default => 'dashboard',
            };

            return redirect()->route($route);
        }

        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])->count(),
            'resolved' => Complaint::where('status', ComplaintStatus::Resolved->value)->count(),
            'avgDays' => $this->getAverageResolutionDays(),
        ];

        $stats['totalComplaints'] = $stats['total'];
        $stats['resolvedComplaints'] = $stats['resolved'];
        $stats['pendingComplaints'] = $stats['pending'];

        return view('pages.home.guest', [
            'complaints' => null,
            'stats' => $stats,
            ...$stats,
        ]);
    }

    private function getAverageResolutionDays(): string
    {
        $avg = Complaint::where('status', ComplaintStatus::Resolved->value)
            ->whereNotNull('updated_at')
            ->selectRaw(
                'AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days',
            )
            ->value('avg_days');

        return $avg !== null ? (string) round((float) $avg) : '—';
    }
}
