<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackStatus;
use App\Models\Department;
use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Resident feedback.
 *
 * The portal as a whole, and the offices a resident has actually dealt with.
 * A rating counts from the moment it is given; a suggestion waits for the
 * administration to read it.
 */
class FeedbackController extends Controller
{
    public function index(FeedbackService $feedback): View
    {
        $user = Auth::user();

        $offices = $feedback->officesFor($user);

        $portal = $feedback->averageFor(null);

        $officeAverages = [];
        foreach ($offices as $office) {
            $officeAverages[$office->id] = $feedback->averageFor($office);
        }

        // What the resident has already said, so the page shows their own
        // ratings back to them rather than asking again.
        $myRatings = Feedback::query()
            ->where('user_id', $user->id)
            ->ratings()
            ->get()
            ->keyBy('department_id');

        $suggestions = Feedback::query()
            ->published()
            ->suggestions()
            ->with(['user:id,display_alias', 'reviewer:id,first_name,last_name'])
            ->latest('published_at')
            ->limit(20)
            ->get();

        return view('feedback.index', [
            'offices' => $offices,
            'portalAverage' => $portal,
            'officeAverages' => $officeAverages,
            'myRatings' => $myRatings,
            'suggestions' => $suggestions,
            'officeAveragesAll' => $feedback->officeAverages(),
        ]);
    }

    public function store(Request $request, FeedbackService $feedback): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'portal_rating' => ['nullable', 'integer', 'between:1,5'],
            'office_ratings' => ['nullable', 'array'],
            'office_ratings.*' => ['integer', 'between:1,5'],
            'suggestion' => ['nullable', 'string', 'max:5000'],
        ], [
            'portal_rating.between' => __('feedback.errors.rating'),
            'office_ratings.*.between' => __('feedback.errors.rating'),
        ]);

        // Only the offices this resident has actually dealt with can be
        // rated: a rating of an office you never wrote to says nothing.
        $rateable = $feedback->officesFor($user)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $given = 0;

        if (! empty($validated['portal_rating'])) {
            $feedback->recordRating($user, null, (int) $validated['portal_rating']);
            $given++;
        }

        foreach ((array) ($validated['office_ratings'] ?? []) as $departmentId => $rating) {
            $departmentId = (int) $departmentId;

            if (! in_array($departmentId, $rateable, true)) {
                continue;
            }

            $feedback->recordRating($user, Department::find($departmentId), (int) $rating);
            $given++;
        }

        $suggestion = trim((string) ($validated['suggestion'] ?? ''));

        if ($suggestion !== '') {
            $feedback->submitSuggestion($user, $suggestion);
            $given++;
        }

        if ($given === 0) {
            return back()->with('error', __('feedback.errors.empty'));
        }

        return redirect()
            ->route('feedback.index')
            ->with('status', __('feedback.sent'));
    }
}
