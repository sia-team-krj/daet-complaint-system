<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect admins and staff to their respective dashboards
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        // Paginated list of this citizen's complaints — latest first
        $base = Complaint::where('user_id', $user->id);
        $counts = [
            'all' => (clone $base)->count(),
            'open' => (clone $base)->whereIn('status', [ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value])->count(),
            'resolved' => (clone $base)->where('status', ComplaintStatus::Resolved->value)->count(),
        ];
        $totalReports = $counts['all'];
        $filtered = clone $base;
        if (request('filter') === 'open') {
            $filtered->whereIn('status', [ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value]);
        } elseif (request('filter') === 'resolved') {
            $filtered->where('status', ComplaintStatus::Resolved->value);
        }
        $complaints = $filtered->with('department')->latest()->paginate(5)->withQueryString();

        // Surface the newest active report with an LGU review update without
        // filling the dashboard with aggregate metric cards.
        $attentionComplaint = Complaint::where('user_id', $user->id)
            ->where('review_status', ComplaintReviewStatus::NeedsInformation->value)
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ])
            ->orderByDesc('updated_at')
            ->first();

        return view('dashboard.index', compact(
            'complaints',
            'attentionComplaint',
            'counts',
            'totalReports',
        ));
    }
}
