<?php

namespace App\Http\Controllers;

use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ComplaintVerificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Department-led verification actions that did not exist before: asking the
 * resident for better evidence, and handing verification to another verifier.
 *
 * Verify/reject deliberately stays on StaffController@review, the established
 * endpoint, so there is only one review form and one set of rules. Both call
 * ComplaintVerificationService so the rules live in exactly one place.
 */
class ComplaintVerificationController extends Controller
{
    public function __construct(
        private readonly ComplaintVerificationService $verification,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Ask the resident for better evidence.
     */
    public function requestInformation(
        Request $request,
        Complaint $complaint,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:15', 'max:1000'],
        ], [
            'question.min' => 'Say specifically what is missing. The resident needs something they can act on.',
        ]);

        try {
            $informationRequest = $this->verification->requestInformation(
                $complaint,
                $request->user(),
                $validated['question'],
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }

        $activityLogger->log(
            'complaint.information_requested',
            "{$request->user()->full_name} asked the resident for more evidence on {$complaint->ticket_id} (round {$informationRequest->round}).",
            $complaint,
            departmentId: (int) $complaint->department_id,
        );

        // The resident is the one who must act, so they hear about it first.
        $this->notifications->notifyUser(
            $complaint->user,
            Notification::COMPLAINT_INFORMATION_REQUESTED,
            "The department needs more information about {$complaint->ticket_id}",
            $request->user(),
            [
                'ticket_id' => $complaint->ticket_id,
                'round' => $informationRequest->round,
                'question' => $informationRequest->question,
            ],
            $complaint,
        );

        return back()->with('status', "The resident has been asked for more evidence on {$complaint->ticket_id}.");
    }

    /**
     * Hand verification to another verifier in the same department.
     */
    public function delegate(
        Request $request,
        Complaint $complaint,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'verifying_staff_id' => ['required', 'exists:users,id'],
        ]);

        $verifier = User::findOrFail($validated['verifying_staff_id']);

        try {
            $this->verification->delegate($complaint, $request->user(), $verifier);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }

        $activityLogger->log(
            'complaint.verification_delegated',
            "Verification of {$complaint->ticket_id} handed to {$verifier->full_name}.",
            $complaint,
            departmentId: (int) $complaint->department_id,
        );

        $this->notifications->verificationDelegated($complaint, $verifier, $request->user());

        return back()->with('status', "Verification of {$complaint->ticket_id} handed to {$verifier->full_name}.");
    }
}
