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
        $complaints = Complaint::where('user_id', $user->id)
            ->with('department')
            ->latest()
            ->paginate(10);

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
            'attentionComplaint'
        ));
    }
}
