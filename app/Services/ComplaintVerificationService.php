<?php

namespace App\Services;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\InformationRequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The one place the rules about settling a complaint live.
 *
 * These rules used to sit inside AdminController, which meant only a global
 * admin could apply them while the staff queue's own error message promised
 * "department review". Both the admin path and the department verifier path now
 * call in here, so the two cannot drift apart.
 */
class ComplaintVerificationService
{
    /** How many times a complaint may go back to the resident before escalating. */
    public const MAX_INFORMATION_ROUNDS = 2;

    /**
     * Decide whether this actor may settle complaints at all, and on this one.
     *
     * @throws ValidationException
     */
    public function authorize(User $actor, Complaint $complaint): void
    {
        if (! $actor->canVerifyComplaints()) {
            throw ValidationException::withMessages([
                'verification' => 'Only a department verifier or an administrator can settle a complaint.',
            ]);
        }

        // Admin works across departments, so the scope check applies to staff only.
        if ($actor->role === 'staff' && (int) $actor->department_id !== (int) $complaint->department_id) {
            throw ValidationException::withMessages([
                'verification' => 'This complaint belongs to another department.',
            ]);
        }
    }

    /**
     * The separation-of-duties control: whoever is doing the physical work can
     * never be the person who signed off that the report was genuine.
     *
     * @throws ValidationException
     */
    public function authorizeNoSelfConflict(Complaint $complaint, ?int $verifierId): void
    {
        if ($verifierId === null || $complaint->assigned_staff_id === null) {
            return;
        }

        if ($verifierId === (int) $complaint->assigned_staff_id) {
            throw ValidationException::withMessages([
                'verification' => 'The assigned staff member cannot verify their own assignment. Ask another verifier.',
            ]);
        }
    }

    /**
     * Apply a review decision. Handles verify and reject, including the priority
     * and public-visibility consequences that already existed on the admin path.
     *
     * @return array<string, mixed> Columns to write onto the complaint.
     *
     * @throws ValidationException
     */
    public function reviewUpdates(
        Complaint $complaint,
        User $actor,
        ComplaintReviewStatus $reviewStatus,
        ?ComplaintPriority $confirmedPriority,
        ?VerificationMethod $method,
        ?string $notes,
    ): array {
        $oldStatus = $complaint->statusEnum;
        $oldPriority = $complaint->confirmedPriorityEnum;

        // needs_information is not a bare review decision: it requires a
        // question the resident can act on. The only legitimate path is
        // requestInformation(), which always carries one.
        if ($reviewStatus === ComplaintReviewStatus::NeedsInformation) {
            throw ValidationException::withMessages([
                'review_status' => 'Use "Ask the resident for more evidence" so the question reaches them.',
            ]);
        }

        if ($reviewStatus === ComplaintReviewStatus::Verified && ! $confirmedPriority) {
            throw ValidationException::withMessages([
                'confirmed_priority' => 'A confirmed priority is required before verifying a complaint.',
            ]);
        }

        // Unverifying must not leave a confirmed priority behind.
        //
        // It deliberately does NOT touch is_public. That flag is the resident's
        // consent, set when they filed and changeable only by them, so a review
        // decision can neither publish a private complaint nor unpublish a
        // public one. Reviewing is about accuracy; consent is theirs.
        if ($reviewStatus !== ComplaintReviewStatus::Verified) {
            $confirmedPriority = null;
        }

        $updates = [
            'review_status' => $reviewStatus->value,
            'confirmed_priority' => $confirmedPriority?->value,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'verifying_staff_id' => $actor->id,
        ];

        if ($method !== null) {
            $updates['verification_method'] = $method->value;
        }

        // The first time the office accepts a report, the clock starts: from
        // then the office owes the resident work. Set once — a later re-review
        // does not restart the wait the resident has already served.
        if ($reviewStatus === ComplaintReviewStatus::Verified && $complaint->verified_at === null) {
            $updates['verified_at'] = now();
        }

        if ($notes !== null && trim($notes) !== '') {
            $updates['review_notes'] = trim($notes);
        }

        if ($reviewStatus === ComplaintReviewStatus::Verified
            && $oldStatus === ComplaintStatus::Submitted) {
            $updates['status'] = ComplaintStatus::UnderReview->value;
        }

        if ($reviewStatus === ComplaintReviewStatus::Rejected
            && in_array($oldStatus, [ComplaintStatus::Submitted, ComplaintStatus::UnderReview], true)) {
            $updates['status'] = ComplaintStatus::Rejected->value;
        }

        // Settling the report closes any outstanding ask to the resident.
        if ($reviewStatus === ComplaintReviewStatus::Verified || $reviewStatus === ComplaintReviewStatus::Rejected) {
            $complaint->informationRequests()
                ->outstanding()
                ->update([
                    'status' => InformationRequestStatus::Resolved->value,
                    'resolved_at' => now(),
                ]);
        }

        return $updates;
    }

    /**
     * Ask the resident for better evidence.
     *
     * A question is mandatory: "needs information" without saying what is
     * missing gives the resident nothing to act on, and the complaint stalls.
     *
     * @throws ValidationException
     */
    public function requestInformation(Complaint $complaint, User $actor, string $question): ComplaintInformationRequest
    {
        $this->authorize($actor, $complaint);
        $this->authorizeNoSelfConflict($complaint, $actor->id);

        $openRequest = $complaint->informationRequests()->outstanding()->latest('id')->first();

        if ($openRequest !== null) {
            throw ValidationException::withMessages([
                'question' => $openRequest->isAnswered()
                    ? 'The resident already replied. Review their answer before asking again.'
                    : 'There is already an open request waiting on the resident.',
            ]);
        }

        $round = (int) $complaint->informationRequests()->max('round') + 1;

        if ($round > self::MAX_INFORMATION_ROUNDS) {
            throw ValidationException::withMessages([
                'question' => 'This complaint has already been sent back to the resident twice. Ask an administrator to review it.',
            ]);
        }

        $request = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $actor->id,
            'question' => trim($question),
            'status' => InformationRequestStatus::Open->value,
            'round' => $round,
        ]);

        $complaint->update([
            'review_status' => ComplaintReviewStatus::NeedsInformation->value,
            'confirmed_priority' => null,
            // is_public is not touched. Asking a resident for more evidence
            // used to silently un-publish their complaint, which meant an office
            // could withdraw a resident's consent just by opening a dialogue
            // with them. Their visibility choice is theirs alone.
            'verifying_staff_id' => $actor->id,
        ]);

        return $request;
    }

    /**
     * Hand verification to another member of the same department.
     *
     * @throws ValidationException
     */
    public function delegate(Complaint $complaint, User $actor, User $verifier): void
    {
        $this->authorize($actor, $complaint);
        $this->authorizeNoSelfConflict($complaint, $verifier->id);

        if ($verifier->role !== 'staff' && $verifier->role !== 'admin') {
            throw ValidationException::withMessages([
                'verifying_staff_id' => 'Verification can only be handed to staff.',
            ]);
        }

        if ($verifier->role === 'staff'
            && (int) $verifier->department_id !== (int) $complaint->department_id) {
            throw ValidationException::withMessages([
                'verifying_staff_id' => 'That person works in another department.',
            ]);
        }

        if (! $verifier->canVerifyComplaints()) {
            throw ValidationException::withMessages([
                'verifying_staff_id' => 'That person is not a verifier.',
            ]);
        }

        $complaint->update(['verifying_staff_id' => $verifier->id]);
    }

    /**
     * Record the resident's reply to an open request.
     *
     * @throws ValidationException
     */
    public function recordResidentReply(
        Complaint $complaint,
        User $resident,
        string $response,
        array $imagePaths,
    ): ComplaintInformationRequest {
        if ((int) $complaint->user_id !== (int) $resident->id) {
            throw ValidationException::withMessages([
                'complaint' => 'This complaint is not yours.',
            ]);
        }

        $request = $complaint->informationRequests()->open()->latest('id')->first();

        if ($request === null
            && $complaint->review_status === ComplaintReviewStatus::NeedsInformation->value) {
            // Orphaned ask: the ticket was flagged needs_information without a
            // question (legacy path). Materialise a request from whatever the
            // reviewer wrote, so the resident has a question to act on.
            $reviewerNote = trim((string) $complaint->review_notes);
            $round = (int) $complaint->informationRequests()->max('round') + 1;

            $complaint->informationRequests()->create([
                'requested_by' => $complaint->verifying_staff_id ?? $complaint->reviewed_by,
                'question' => $reviewerNote !== ''
                    ? $reviewerNote
                    : __('complaint_show.action.fallback_question'),
                'status' => InformationRequestStatus::Open->value,
                'round' => $round,
            ]);

            $request = $complaint->informationRequests()->open()->latest('id')->first();
        }

        if ($request === null) {
            throw ValidationException::withMessages([
                'complaint' => 'There is nothing waiting for your reply on this complaint.',
            ]);
        }

        $request->update([
            'status' => InformationRequestStatus::Answered->value,
            'response_text' => trim($response),
            'response_image_paths' => $imagePaths !== [] ? $imagePaths : null,
            'responded_at' => now(),
        ]);

        return $request;
    }
}
