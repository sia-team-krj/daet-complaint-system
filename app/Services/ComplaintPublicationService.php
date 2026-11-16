<?php

namespace App\Services;

use App\Enums\PublicationStatus;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The publication workflow for complaints that name somebody.
 *
 * A gated complaint — how you were treated at the LGU, the plastic policy —
 * is filed private. It is handed to the Mayor's office automatically once:
 *
 *   1. the owning office has settled it (resolved or closed),
 *   2. the information has been verified as true,
 *
 * and then the Mayor's office redacts the public copy and either approves it
 * — and the redacted version goes on the public register — or declines, and
 * the complaint is never published.
 *
 * The handling office never writes the public copy and never asks for the
 * handoff by hand: resolving a verified report is the handoff.
 *
 * The name of the party never appears on the public register in either case.
 * The resident is notified of the handoff, the approval, and the refusal.
 */
class ComplaintPublicationService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * Send a settled, verified report to the Mayor's office.
     *
     * Idempotent: a report that already has a publication state is left alone.
     * This is called when a department resolves a complaint and when a review
     * decision makes a settled report verified, so it does not matter which
     * of the two lands last.
     */
    public function autoSubmit(Complaint $complaint, ?User $actor = null): bool
    {
        if (! $complaint->isPrivacyGated()) {
            return false;
        }

        if ($complaint->publicationStatus() !== null) {
            return false;
        }

        if (ComplaintPrivacyService::handoffBlockers($complaint) !== []) {
            return false;
        }

        DB::transaction(function () use ($complaint, $actor): void {
            $complaint->update([
                'publication_status' => PublicationStatus::Pending->value,
                'publication_requested_at' => now(),
                'publication_requested_by' => $actor?->id,
                'publication_note' => null,
            ]);

            $complaint->refresh();

            $this->activityLogger->log(
                'complaint.publication_requested',
                "Complaint {$complaint->ticket_id} was sent automatically to the Mayor's office for publication review.",
                $complaint,
                metadata: [
                    'publication_status' => PublicationStatus::Pending->value,
                    'automatic' => true,
                ],
                actor: $actor,
            );
        });

        try {
            $this->notifyApprovalOffice($complaint, $actor);
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $this->notifyResidentOfHandoff($complaint, $actor);
        } catch (Throwable $exception) {
            report($exception);
        }

        return true;
    }

    /**
     * Save the redacted public copy. Only the Mayor's office may write it, and
     * only while the report is waiting for its decision.
     */
    public function saveRedaction(
        Complaint $complaint,
        User $actor,
        ?string $title,
        ?string $description,
        ?string $note = null,
    ): void {
        abort_unless($complaint->isPrivacyGated(), 422, 'This complaint is not in a category that needs a publication decision.');
        abort_unless(
            ComplaintPrivacyService::canApprove($actor),
            403,
            'Only the Mayor\'s office can redact a report for publication.',
        );

        if ($complaint->publicationStatus() !== PublicationStatus::Pending) {
            throw ValidationException::withMessages([
                'publication' => 'This report is not waiting for a publication decision.',
            ]);
        }

        $title = $this->clean($title);
        $description = $this->clean($description);

        if ($title === null || $description === null) {
            throw ValidationException::withMessages([
                'redacted_title' => 'Write both the redacted title and the redacted description before saving.',
            ]);
        }

        DB::transaction(function () use ($complaint, $actor, $title, $description, $note): void {
            $complaint->update([
                'redacted_title' => $title,
                'redacted_description' => $description,
            ]);

            $complaint->refresh();

            $this->activityLogger->log(
                'complaint.publication_redacted',
                "{$actor->full_name} saved the redacted public copy for complaint {$complaint->ticket_id}.",
                $complaint,
                metadata: [
                    'redacted_title' => $title,
                    'redacted_description' => $description,
                    'note' => $this->clean($note),
                ],
                actor: $actor,
            );
        });
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

        if ($complaint->publicationStatus() !== PublicationStatus::Pending) {
            throw ValidationException::withMessages([
                'publication' => 'This report is not waiting for a publication decision.',
            ]);
        }

        $blockers = ComplaintPrivacyService::approvalBlockers($complaint);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'publication' => $blockers,
            ]);
        }

        DB::transaction(function () use ($complaint, $actor, $status, $note): void {
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
                actor: $actor,
            );
        });

        try {
            $this->notifyResident($complaint, $actor, $status, $note);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Tell the resident that their report is with the Mayor's office for a
     * publication decision. The handoff is automatic, so the message says
     * what happened rather than asking them to do anything.
     */
    private function notifyResidentOfHandoff(Complaint $complaint, ?User $actor): void
    {
        $resident = $complaint->user;

        if ($resident === null) {
            return;
        }

        $this->notifications->notifyUser(
            $resident,
            Notification::COMPLAINT_PUBLICATION_REQUESTED,
            "{$complaint->ticket_id}: your report is with the Mayor's office for publication review",
            $actor,
            [
                'ticket_id' => $complaint->ticket_id,
                'publication_status' => PublicationStatus::Pending->value,
            ],
            $complaint,
        );
    }

    /**
     * Tell the Mayor's office there is a publication decision waiting.
     */
    private function notifyApprovalOffice(Complaint $complaint, ?User $actor): void
    {
        $recipients = User::query()
            ->whereIn('role', ['staff', 'department_admin'])
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

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
