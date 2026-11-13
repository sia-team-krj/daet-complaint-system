<?php

namespace App\Http\Controllers;

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

        return view('admin.feedback.index', [
            'pending' => $pending,
            'published' => $published,
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
}
