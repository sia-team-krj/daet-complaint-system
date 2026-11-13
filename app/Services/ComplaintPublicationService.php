<?php

namespace App\Services;

use App\Enums\PublicationStatus;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The publication workflow for complaints that name somebody.
 *
 * A gated complaint — how you were treated at the LGU, the plastic policy —
 * is filed private. It can only be published once:
 *
 *   1. the owning office has settled it (resolved or closed),
 *   2. the information has been verified as true,
 *   3. the sensitive parts have been redacted,
 *
 * and then the owning office asks the Mayor's office to approve it. The
 * Mayor's office either approves — and the redacted version goes on the
 * public register — or declines, and the complaint is never published.
 *
 * The name of the party never appears on the public register in either case.
 * The resident is notified of the request, the approval, and the refusal.
 */
class ComplaintPublicationService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * The owning office asks the Mayor's office to publish the complaint.
     *
     * Refused unless the complaint is settled, verified and redacted: asking
     * for publication before those is asking the Mayor's office to sign off
     * on something that is not ready.
     */
    public function request(Complaint $complaint, User $actor, ?string $note = null): void
    {
        abort_unless($complaint->isPrivacyGated(), 422, 'This complaint is not in a category that needs a publication decision.');

        $blockers = $this->blockersBeforeRequest($complaint);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'publication' => $blockers,
            ]);
        }

        if ($complaint->publicationStatus() === PublicationStatus::Approved) {
            throw ValidationException::withMessages([
                'publication' => 'This complaint has already been approved for publication.',
            ]);
        }

        DB::transaction(function () use ($complaint, $actor, $note) {
            $complaint->update([
                'publication_status' => PublicationStatus::Pending->value,
                'publication_requested_at' => now(),
                'publication_requested_by' => $actor->id,
                'publication_note' => $note,
            ]);

            $complaint->refresh();

            $this->activityLogger->log(
                'complaint.publication_requested',
                "{$actor->full_name} asked the Mayor's office to publish complaint {$complaint->ticket_id}.",
                $complaint,
                metadata: [
                    'publication_status' => PublicationStatus::Pending->value,
                    'note' => $note,
                ],
            );
        });

        $this->notifyApprovalOffice($complaint, $actor);
        $this->notifyResidentOfRequest($complaint, $actor);
    }

    /**
     * The Mayor's office approves the publication.
     */
    public function approve(Complaint $complaint, User $actor, ?string $note = null): void
    {
        $this->decide($complaint, $actor, PublicationStatus::Approved, $note);
    }

    /**
     * The Mayor's office declines: the complaint is never published.
     */
    public function decline(Complaint $complaint, User $actor, string $reason): void
    {
        $this->decide($complaint, $actor, PublicationStatus::Declined, $reason);
    }

    private function decide(
        Complaint $complaint,
        User $actor,
        PublicationStatus $status,
        ?string $note,
    ): void {
        abort_unless($complaint->isPrivacyGated(), 422, 'This complaint is not in a category that needs a publication decision.');
        abort_unless(
            ComplaintPrivacyService::canApprove($actor),
            403,
            'Only the Mayor\'s office can decide whether this complaint is published.',
        );

        if ($status === PublicationStatus::Approved) {
            $blockers = $this->blockersBeforeRequest($complaint);

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'publication' => $blockers,
                ]);
            }
        }

        DB::transaction(function () use ($complaint, $actor, $status, $note) {
            $complaint->update([
                'publication_status' => $status->value,
                'publication_decided_at' => now(),
                'publication_decided_by' => $actor->id,
                'publication_note' => $note,
            ]);

            $complaint->refresh();

            $this->activityLogger->log(
                $status === PublicationStatus::Approved
                    ? 'complaint.publication_approved'
                    : 'complaint.publication_declined',
                $status === PublicationStatus::Approved
                    ? "{$actor->full_name} approved complaint {$complaint->ticket_id} for publication on the transparency page."
                    : "{$actor->full_name} declined to publish complaint {$complaint->ticket_id}. It stays private.",
                $complaint,
                metadata: [
                    'publication_status' => $status->value,
                    'note' => $note,
                ],
            );
        });

        $this->notifyResident($complaint, $actor, $status, $note);
    }

    /**
     * What has to be true before the Mayor's office is even asked.
     *
     * @return list<string>
     */
    private function blockersBeforeRequest(Complaint $complaint): array
    {
        $blockers = [];

        if (! ComplaintPrivacyService::isSettled($complaint)) {
            $blockers[] = 'Settle this report first — resolve or close it.';
        }

        if (! ComplaintPrivacyService::isVerified($complaint)) {
            $blockers[] = 'Verify that the information is true before asking for publication.';
        }

        if (! ComplaintPrivacyService::isRedacted($complaint)) {
            $blockers[] = 'Write the redacted title and description before asking for publication.';
        }

        return $blockers;
    }

    /**
     * Tell the resident that the office has asked the Mayor's office to
     * decide whether their report appears on the transparency page.
     */
    private function notifyResidentOfRequest(Complaint $complaint, User $actor): void
    {
        $resident = $complaint->user;

        if ($resident === null) {
            return;
        }

        $this->notifications->notifyUser(
            $resident,
            Notification::COMPLAINT_PUBLICATION_REQUESTED,
            "{$complaint->ticket_id}: the office asked the Mayor's office to publish your report",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'publication_status' => PublicationStatus::Pending->value,
            ],
            $complaint,
        );
    }

    /**
     * Tell the Mayor's office there is a publication request waiting.
     */
    private function notifyApprovalOffice(Complaint $complaint, User $actor): void
    {
        $recipients = User::query()
            ->where('role', 'staff')
            ->where('is_active', true)
            ->whereHas('department', fn ($query) => $query->where('code', ComplaintPrivacyService::approvalOfficeCode()))
            ->get();

        foreach ($recipients as $recipient) {
            $this->notifications->notifyUser(
                $recipient,
                Notification::COMPLAINT_PUBLICATION_REQUESTED,
                "{$complaint->ticket_id} is waiting for a publication decision",
                $actor,
                [
                    'ticket_id' => $complaint->ticket_id,
                    'publication_status' => PublicationStatus::Pending->value,
                ],
                $complaint,
            );
        }
    }

    /**
     * Tell the resident what was decided about their report.
     */
    private function notifyResident(
        Complaint $complaint,
        User $actor,
        PublicationStatus $status,
        ?string $note,
    ): void {
        $resident = $complaint->user;

        if ($resident === null) {
            return;
        }

        $title = $status === PublicationStatus::Approved
            ? "{$complaint->ticket_id} was published on the transparency page"
            : "{$complaint->ticket_id} was not published on the transparency page";

        $this->notifications->notifyUser(
            $resident,
            $status === PublicationStatus::Approved
                ? Notification::COMPLAINT_PUBLICATION_APPROVED
                : Notification::COMPLAINT_PUBLICATION_DECLINED,
            $title,
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'publication_status' => $status->value,
                'note' => $note,
            ],
            $complaint,
        );
    }
}
