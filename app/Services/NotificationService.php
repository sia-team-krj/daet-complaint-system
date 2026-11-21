<?php

namespace App\Services;

use App\Enums\ComplaintReviewStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\IdentityVerification;
use App\Models\Notification;
use App\Models\User;
use App\Services\ComplaintPrivacyService;
use Illuminate\Support\Collection;

/**
 * Creates the notifications the verification workflow implies.
 *
 * Recipients are always resolved from the data — the department a complaint
 * belongs to, the owner of the complaint, the staff member who asked — never
 * from a caller-supplied list, so a notification cannot be aimed at the wrong
 * person. An actor never notifies themselves.
 */
class NotificationService
{
    /** How long a resident may sit on a request before it counts as stalled. */
    public const STALE_AFTER_DAYS = 3;

    /**
     * Everyone in the complaint's department who may settle complaints, minus
     * the actor and minus whoever is already doing the physical work.
     *
     * @return Collection<int, User>
     */
    public function departmentVerifiersFor(Complaint $complaint, ?User $actor = null): Collection
    {
        if ($complaint->department_id === null) {
            return collect();
        }

        return User::query()
            ->whereIn('role', ['staff', 'department_admin'])
            ->where('department_id', $complaint->department_id)
            // Department Administrators verify by role; ordinary staff need
            // the explicit capability.
            ->where(function ($query): void {
                $query->where('role', 'department_admin')
                    ->orWhere('can_verify', true);
            })
            ->where('is_active', true)
            ->when($actor !== null, fn ($query) => $query->where('id', '!=', $actor->id))
            ->when(
                $complaint->assigned_staff_id !== null,
                fn ($query) => $query->where('id', '!=', $complaint->assigned_staff_id)
            )
            ->get();
    }

    /**
     * Administrators can verify across departments, so they are the safety net
     * when no department verifier can take the work.
     *
     * @return Collection<int, User>
     */
    private function adminFallbackFor(?User $actor = null): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->when($actor !== null, fn ($query) => $query->where('id', '!=', $actor->id))
            ->get();
    }

    public function notifyDepartmentVerifiers(
        Complaint $complaint,
        string $type,
        string $title,
        ?User $actor = null,
        array $context = [],
    ): int {
        $recipients = $this->departmentVerifiersFor($complaint, $actor);

        // A department with no verifier, or one whose only verifier is the
        // person who acted, would otherwise leave a complaint in a queue that
        // nobody can settle. Administrators can verify across departments, so
        // they are the fallback rather than letting the report go unnoticed.
        if ($recipients->isEmpty()) {
            $recipients = $this->adminFallbackFor($actor);
        }

        return $this->notifyMany(
            $recipients,
            $type,
            $title,
            $complaint,
            $context,
        );
    }

    public function notifyUser(
        User $recipient,
        string $type,
        string $title,
        ?User $actor = null,
        array $context = [],
        $subject = null,
    ): ?Notification {
        if ($actor !== null && (int) $actor->id === (int) $recipient->id) {
            return null;
        }

        if (! $this->shouldReceive($recipient, $type)) {
            return null;
        }

        return Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'context' => $context !== [] ? $context : null,
        ]);
    }

    /**
     * A report is waiting on a verifier.
     */
    public function complaintNeedsVerification(Complaint $complaint, ?User $actor = null): int
    {
        return $this->notifyDepartmentVerifiers(
            $complaint,
            Notification::COMPLAINT_NEEDS_VERIFICATION,
            "{$complaint->ticket_id} is waiting to be verified",
            $actor,
            ['review_status' => $complaint->review_status],
        );
    }

    /**
     * The moderation detector flagged the report and the office is reviewing
     * it. The resident is told plainly — the flag is about the report, never an
     * accusation against them — so they are not left waiting without knowing.
     * The actor is deliberately left unset at intake: the filer is the owner,
     * and an owner must not be told about their own filing.
     */
    public function moderationFlagged(Complaint $complaint, ?User $actor = null): ?Notification
    {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_MODERATION_FLAGGED,
            __('notifications.moderation_flagged.title', ['ticket' => $complaint->ticket_id]),
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'spam_status' => $complaint->spam_status,
                'spam_score' => $complaint->spam_score,
                'reasons' => $complaint->spam_reasons ?? [],
                'duplicate_of_id' => $complaint->duplicate_of_id,
                'similarity_score' => $complaint->similarity_score,
            ],
            $complaint,
        );
    }

    public function identitySubmitted(IdentityVerification $verification): ?Notification
    {
        return $this->notifyUser(
            $verification->user,
            Notification::IDENTITY_SUBMITTED,
            __('identity.notifications.submitted.title', ['id_type' => $verification->id_type]),
            context: ['verification_id' => $verification->id],
            subject: $verification,
        );
    }

    public function identityApproved(IdentityVerification $verification): ?Notification
    {
        return $this->notifyUser(
            $verification->user,
            Notification::IDENTITY_APPROVED,
            __('identity.notifications.approved.title'),
            context: ['verification_id' => $verification->id],
            subject: $verification,
        );
    }

    public function identityRejected(IdentityVerification $verification): ?Notification
    {
        return $this->notifyUser(
            $verification->user,
            Notification::IDENTITY_REJECTED,
            __('identity.notifications.rejected.title'),
            context: ['verification_id' => $verification->id],
            subject: $verification,
        );
    }

    public function identityRevoked(IdentityVerification $verification): ?Notification
    {
        return $this->notifyUser(
            $verification->user,
            Notification::IDENTITY_REVOKED,
            __('identity.notifications.revoked.title'),
            context: ['verification_id' => $verification->id],
            subject: $verification,
        );
    }

    public function verificationDelegated(Complaint $complaint, User $verifier, ?User $actor = null): ?Notification
    {
        return $this->notifyUser(
            $verifier,
            Notification::COMPLAINT_VERIFICATION_DELEGATED,
            "Verification of {$complaint->ticket_id} was handed to you",
            $actor,
            ['ticket_id' => $complaint->ticket_id],
            $complaint,
        );
    }

    /**
     * The resident answered. Whoever asked is the one who needs to know.
     */
    public function informationProvided(Complaint $complaint, ComplaintInformationRequest $request): ?Notification
    {
        $requester = $request->requester;

        if ($requester === null) {
            return null;
        }

        return $this->notifyUser(
            $requester,
            Notification::COMPLAINT_INFORMATION_PROVIDED,
            "The resident replied about {$complaint->ticket_id}",
            null,
            [
                'ticket_id' => $complaint->ticket_id,
                'round' => $request->round,
            ],
            $complaint,
        );
    }

    /**
     * A request has gone unanswered long enough that someone should notice.
     */
    /**
     * The office accepted a report and has not started the work in time.
     *
     * The people told are the ones who can do the work — the department's
     * staff — plus the administrators, because a department that has let a
     * report slip is something the municipality should be able to see rather
     * than something each office has to notice alone.
     */
    public function workOverdue(Complaint $complaint, int $daysOverdue): int
    {
        $recipients = $this->departmentStaffFor($complaint)
            ->merge($this->adminFallbackFor())
            ->unique('id');

        return $this->notifyMany(
            $recipients,
            Notification::COMPLAINT_WORK_OVERDUE,
            "{$complaint->ticket_id} is {$daysOverdue} day(s) past its deadline",
            $complaint,
            [
                'ticket_id' => $complaint->ticket_id,
                'days_overdue' => $daysOverdue,
                'status' => $complaint->status,
            ],
        );
    }

    /**
     * The staff of the complaint's department who can be asked to do the work.
     *
     * @return Collection<int, User>
     */
    public function departmentStaffFor(Complaint $complaint): Collection
    {
        if ($complaint->department_id === null) {
            return collect();
        }

        return User::query()
            ->whereIn('role', ['staff', 'department_admin'])
            ->where('department_id', $complaint->department_id)
            ->where('is_active', true)
            ->get();
    }

    public function informationStale(Complaint $complaint, ComplaintInformationRequest $request): int
    {
        return $this->notifyDepartmentVerifiers(
            $complaint,
            Notification::COMPLAINT_INFORMATION_STALE,
            "{$complaint->ticket_id} has been waiting on the resident for "
                .$request->created_at->diffInDays().' days',
            context: [
                'ticket_id' => $complaint->ticket_id,
                'days' => (int) $request->created_at->diffInDays(),
                // Keyed on the request, not the complaint: the subject stays the
                // complaint so the link lands correctly, but the daily sweep has
                // to recognise it already warned about this specific ask.
                'request_id' => $request->id,
            ],
        );
    }

    /**
     * The department settled the review, which is the event a resident is
     * actually waiting for. Kept separate from statusChanged so an approval
     * does not masquerade as routine progress and a rejection is unmistakable.
     */
    public function reviewUpdated(
        Complaint $complaint,
        ComplaintReviewStatus $reviewStatus,
        ?User $actor = null,
    ): ?Notification {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        $action = match ($reviewStatus) {
            ComplaintReviewStatus::Verified => 'was verified',
            ComplaintReviewStatus::Rejected => 'was rejected',
            ComplaintReviewStatus::Duplicate => 'was marked as a duplicate',
            ComplaintReviewStatus::NeedsInformation => 'needs more information',
            ComplaintReviewStatus::Escalated => 'was escalated',
            ComplaintReviewStatus::Pending => null,
        };

        // "Pending verification" is the state the resident already knows about;
        // it is not news worth a bell.
        if ($action === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_REVIEW_UPDATED,
            "{$complaint->ticket_id} {$action}",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'review_status' => $reviewStatus->value,
                'status' => $complaint->status,
            ],
            $complaint,
        );
    }

    /**
     * Office staff marked the report misleading or spam — the office's
     * rejection of it. The people told are the Office of the Mayor's office
     * staff and the super admin: this is a management decision, not routine
     * progress.
     */
    public function complaintMarkedMisleadingOrSpam(Complaint $complaint, ?User $actor = null): int
    {
        $recipients = $this->officeOfTheMayorStaffFor($complaint)
            ->merge($this->superAdmins());

        return $this->notifyMany(
            $recipients,
            Notification::COMPLAINT_MARKED_MISLEADING_OR_SPAM,
            __('notifications.marked_misleading_or_spam.title', ['ticket' => $complaint->ticket_id]),
            $complaint,
            [
                'ticket_id' => $complaint->ticket_id,
                'marking' => $complaint->markingEnum()?->value,
                'marked_by' => $complaint->markedBy?->full_name,
            ],
        );
    }

    /**
     * The staff of the Office of the Mayor (the configured approval office
     * code, default `OM`). Office-scope staff only — field officers of the
     * complaint's own office already know about a report they rejected, so
     * the management route is the point of this notification.
     *
     * @return Collection<int, User>
     */
    private function officeOfTheMayorStaffFor(Complaint $complaint): Collection
    {
        $code = trim((string) ComplaintPrivacyService::approvalOfficeCode());

        if ($code === '') {
            return collect();
        }

        $office = Department::query()
            ->whereRaw('LOWER(TRIM(code)) = ?', strtolower($code))
            ->first();

        if ($office === null) {
            return collect();
        }

        return User::query()
            ->where('department_id', $office->id)
            ->whereIn('role', ['staff', 'department_admin'])
            ->where('is_active', true)
            ->get();
    }

    /**
     * Global administrators — the safety net when the Office of the Mayor
     * has no staff on file.
     *
     * @return Collection<int, User>
     */
    private function superAdmins(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Hand the physical work to a staff member.
     */
    public function complaintAssigned(Complaint $complaint, User $assignee, ?User $actor = null): ?Notification
    {
        return $this->notifyUser(
            $assignee,
            Notification::COMPLAINT_ASSIGNED,
            "{$complaint->ticket_id} was assigned to you",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'department_id' => $complaint->department_id,
            ],
            $complaint,
        );
    }

    /**
     * The resident's own complaint moved.
     */
    public function statusChanged(Complaint $complaint, string $label, ?User $actor = null): ?Notification
    {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_STATUS_CHANGED,
            "{$complaint->ticket_id} is now {$label}",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'status' => $complaint->status,
                'review_status' => $complaint->review_status,
            ],
            $complaint,
        );
    }

    /**
     * The office paused the work on a complaint. The resident is told why,
     * rather than being left to guess at a silence.
     */
    public function complaintPutOnHold(Complaint $complaint, ?User $actor = null): ?Notification
    {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_PUT_ON_HOLD,
            "{$complaint->ticket_id} is on hold",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'hold_reason' => $complaint->hold_reason,
                'hold_note' => $complaint->holdNote(),
                'expected_resume_at' => $complaint->hold_expected_resume_at?->toDateString(),
            ],
            $complaint,
        );
    }

    /**
     * The office took the hold off and is carrying on with the work.
     */
    public function complaintHoldRemoved(Complaint $complaint, ?User $actor = null): ?Notification
    {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_HOLD_REMOVED,
            "{$complaint->ticket_id} is being worked on again",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'held_minutes' => $complaint->heldMinutes(),
            ],
            $complaint,
        );
    }

    /**
     * The work was resolved; the resident has not confirmed it yet.
     *
     * The case is not closed until they say so, so they can still say it was
     * not fixed — but only if they know it is waiting on them.
     */
    public function resolutionReminder(Complaint $complaint, ?User $actor = null): ?Notification
    {
        $owner = $complaint->user;

        if ($owner === null) {
            return null;
        }

        return $this->notifyUser(
            $owner,
            Notification::COMPLAINT_RESOLUTION_REMINDER,
            "{$complaint->ticket_id} was resolved — please confirm it",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'resolved_at' => $complaint->resolved_at?->toDateString(),
            ],
            $complaint,
        );
    }

    /**
     * Recipients who are waiting on the resident are not told about status
     * noise; only the owner of a complaint hears about its own progress.
     *
     * @param  Collection<int, User>  $recipients
     * @return int
     */
    private function notifyMany(
        Collection $recipients,
        string $type,
        string $title,
        Complaint $subject,
        array $context,
    ): int {
        $created = 0;

        foreach ($recipients as $recipient) {
            $notification = $this->notifyUser(
                $recipient,
                $type,
                $title,
                null,
                $context,
                $subject,
            );

            if ($notification !== null) {
                $created++;
            }
        }

        return $created;
    }

    private function shouldReceive(User $recipient, string $type): bool
    {
        if (! $recipient->is_active) {
            return false;
        }

        // A paused account is not told about work it cannot act on.
        return true;
    }

    /**
     * Requests nobody closed, oldest first. This is the visible queue that stops
     * an unclosed ask from rotting silently — auto-expiry was rejected because
     * it would quietly discard a resident's obligation.
     *
     * @return Collection<int, ComplaintInformationRequest>
     */
    public function staleRequests(int $days = self::STALE_AFTER_DAYS, int $limit = 50): Collection
    {
        return ComplaintInformationRequest::query()
            ->with(['complaint.department', 'requester'])
            ->outstanding()
            ->where('created_at', '<=', now()->subDays($days))
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }
}
