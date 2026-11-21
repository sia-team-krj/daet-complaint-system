<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackStatus;
use App\Models\ComplaintFeedback;
use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reading what residents asked for.
 *
 * A suggestion is prose about the portal, and prose goes out reviewed. The
 * ratings do not wait for anyone: they count from the moment they are given.
 *
 * Complaint feedback comments are reviewed the same way before they appear on
 * the public transparency page for a specific complaint.
 */
class AdminFeedbackController extends Controller
{
    public function index(FeedbackService $feedback): View
    {
        $pending = Feedback::query()
            ->pending()
            ->suggestions()
            ->with(['user:id,display_alias,first_name,last_name'])
            ->oldest('created_at')
            ->paginate(20);

        $published = Feedback::query()
            ->published()
            ->suggestions()
            ->with(['user:id,display_alias', 'reviewer:id,first_name,last_name'])
            ->latest('published_at')
            ->limit(20)
            ->get();

        $pendingComplaintFeedback = ComplaintFeedback::query()
            ->pending()
            ->whereNotNull('comment')
            ->with([
                'user:id,display_alias,first_name,last_name',
                'complaint:id,ticket_id,title,redacted_title',
            ])
            ->oldest('created_at')
            ->paginate(20, ['*'], 'complaint_page');

        $publishedComplaintFeedback = ComplaintFeedback::query()
            ->published()
            ->whereNotNull('comment')
            ->with([
                'user:id,display_alias',
                'complaint:id,ticket_id,title,redacted_title',
                'reviewer:id,first_name,last_name',
            ])
            ->latest('published_at')
            ->limit(20)
            ->get();

        return view('admin.feedback.index', [
            'pending' => $pending,
            'published' => $published,
            'pendingComplaintFeedback' => $pendingComplaintFeedback,
            'publishedComplaintFeedback' => $publishedComplaintFeedback,
            'portalAverage' => $feedback->averageFor(null),
            'officeAverages' => $feedback->officeAverages(),
        ]);
    }

    public function publish(Request $request, Feedback $feedback, FeedbackService $service): RedirectResponse
    {
        $service->review($feedback, $request->user(), true);

        return redirect()
            ->route('admin.feedback.index')
            ->with('status', __('admin_settings.feedback.published'));
    }

    public function withhold(Request $request, Feedback $feedback, FeedbackService $service): RedirectResponse
    {
        $service->review($feedback, $request->user(), false);

        return redirect()
            ->route('admin.feedback.index')
            ->with('status', __('admin_settings.feedback.withheld'));
    }

    public function publishComplaint(Request $request, ComplaintFeedback $complaintFeedback): RedirectResponse
    {
        $complaintFeedback->update([
            'status' => FeedbackStatus::Published->value,
            'published_at' => now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.feedback.index')
            ->with('status', __('admin_settings.feedback.complaint_published'));
    }

    public function withholdComplaint(Request $request, ComplaintFeedback $complaintFeedback): RedirectResponse
    {
        $complaintFeedback->update([
            'status' => FeedbackStatus::Withheld->value,
            'published_at' => null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.feedback.index')
            ->with('status', __('admin_settings.feedback.complaint_withheld'));
    }
}
