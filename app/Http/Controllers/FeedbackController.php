<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // ratings back to them rather than asking again. A rating is final, so
        // these render as a read-only score, never as controls.
        $myRatings = Feedback::query()
            ->where('user_id', $user->id)
            ->ratings()
            ->get()
            ->keyBy('department_id');

        $portalRating = $myRatings->get(null);

        $unratedOffices = $offices->reject(
            fn (Department $office): bool => $myRatings->has($office->id),
        );

        // One suggestion a day. Administrators read the queue rather than
        // write to it, so the daily allowance is theirs to ignore.
        $suggestionSentToday = ! $user->isAdmin() && $feedback->hasSuggestedToday($user);

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
            'portalRating' => $portalRating,
            'suggestionSentToday' => $suggestionSentToday,
            'canSubmit' => $portalRating === null
                || $unratedOffices->isNotEmpty()
                || ! $suggestionSentToday,
            'suggestions' => $suggestions,
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

        $suggestion = trim((string) ($validated['suggestion'] ?? ''));

        // The page disables the box at the limit, and this is the server's
        // half of the same rule: the request is refused rather than silently
        // dropping the resident's words. Staff are limited too; only the
        // administration, which reads the queue, is exempt.
        if ($suggestion !== '' && ! $user->isAdmin() && $feedback->hasSuggestedToday($user)) {
            return back()
                ->withInput()
                ->with('error', __('feedback.errors.suggestion_limit'));
        }

        // Only the offices that have resolved or closed a report for this
        // resident can be rated: a rating of an office you never finished a
        // job with says nothing about the service.
        $rateable = $feedback->officesFor($user)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $rated = 0;
        $alreadyRated = 0;

        if (! empty($validated['portal_rating'])) {
            $record = $feedback->recordRating($user, null, (int) $validated['portal_rating']);

            if ($record->wasRecentlyCreated) {
                $rated++;
            } else {
                $alreadyRated++;
            }
        }

        foreach ((array) ($validated['office_ratings'] ?? []) as $departmentId => $rating) {
            $departmentId = (int) $departmentId;

            if (! in_array($departmentId, $rateable, true)) {
                continue;
            }

            $record = $feedback->recordRating($user, Department::find($departmentId), (int) $rating);

            if ($record->wasRecentlyCreated) {
                $rated++;
            } else {
                $alreadyRated++;
            }
        }

        $suggestionGiven = false;

        if ($suggestion !== '') {
            $feedback->submitSuggestion($user, $suggestion);
            $suggestionGiven = true;
        }

        if ($rated === 0 && ! $suggestionGiven) {
            return back()
                ->withInput()
                ->with('error', $alreadyRated > 0
                    ? __('feedback.errors.rating_locked')
                    : __('feedback.errors.empty'));
        }

        $message = match (true) {
            $rated > 0 && $suggestionGiven => __('feedback.sent_both'),
            $suggestionGiven => __('feedback.sent_suggestion'),
            default => __('feedback.sent_rating'),
        };

        return redirect()
            ->route('feedback.index')
            ->with('status', $message);
    }
}
