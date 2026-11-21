<?php

namespace App\Services;

use App\Enums\ComplaintMarking;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Office staff mark a report as misleading or spam — the office's own
 * rejection of the report.
 *
 * Everything a field officer writes here is recorded: the marking, the
 * description and the comment, the staff member, and the status change to
 * `ComplaintStatus::Rejected` on the immutable log. The rules about who may
 * mark and what they must write live here, so the controller and any future
 * caller cannot drift apart.
 *
 * The "Field Officer" (role `staff`) always gives the office a description and
 * a comment — a mark carries no meaning without them. The Department
 * Administrator (the Inspector-equivalent office staff) may mark without
 * them; they are the record-keepers, not the field force.
 *
 * Office management is notified: the Office of the Mayor's office staff and
 * the super admin.
 */
class ComplaintMarkingService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Mark a report misleading or spam.
     *
     * @throws ValidationException
     */
    public function mark(
        Complaint $complaint,
        User $actor,
        ComplaintMarking $marking,
        ?string $description = null,
        ?string $comment = null,
    ): void {
        if (! $actor->isDepartmentStaff()) {
            throw ValidationException::withMessages([
                'marking' => 'Only a Department Field Officer or Inspector can mark a report.',
            ]);
        }

        // A finished report (rejected, resolved, closed) has nothing left to
        // mark; only open work can be rejected as misleading or spam.
        if (! $complaint->statusEnum->isOpen()) {
            throw ValidationException::withMessages([
                'marking' => 'This report is no longer open to mark.',
            ]);
        }

        if ($complaint->isMarked()) {
            throw ValidationException::withMessages([
                'marking' => 'This report has already been marked.',
            ]);
        }

        // The field officer is the voice of the field: their mark must carry
        // a description of what is misleading or spam, and a comment on the
        // report itself. The Inspector may act on the strength of the report.
        if ($actor->role === 'staff') {
            $required = [];

            if (trim((string) $description) === '') {
                $required['description'] = __('staff.marking.description_required');
            }

            if (trim((string) $comment) === '') {
                $required['comment'] = __('staff.marking.comment_required');
            }

            if ($required !== []) {
                throw ValidationException::withMessages($required);
            }
        }

        $description = trim((string) $description);
        $comment = trim((string) $comment);
        $previousStatus = $complaint->statusEnum;

        DB::transaction(function () use ($complaint, $actor, $marking, $description, $comment, $previousStatus): void {
            $complaint->update([
                'marking' => $marking->value,
                'mark_description' => $description !== '' ? $description : null,
                'mark_comment' => $comment !== '' ? $comment : null,
                'marked_by' => $actor->id,
                'marked_at' => now(),
                // The marking is the office's rejection of the report: the
                // report stops being active work, the same as any other
                // terminal rejection.
                'status' => ComplaintStatus::Rejected->value,
            ]);

            // The immutable timeline carries the office's reason and comment
            // next to the status change, so the record reads as one fact.
            $timelineComment = "Marked as {$marking->label()}: {$description}";
            if ($comment !== '') {
                $timelineComment .= " — {$comment}";
            }

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => $previousStatus->value,
                'new_status' => ComplaintStatus::Rejected->value,
                'comment' => $timelineComment,
                'actor_id' => $actor->id,
                'is_internal' => false,
            ]);

            $this->activityLogger->log(
                'complaint.marked',
                "{$actor->full_name} marked complaint {$complaint->ticket_id} as {$marking->label()} and rejected it.",
                $complaint,
                metadata: [
                    'marking' => $marking->value,
                    'description' => $description,
                    'comment' => $comment,
                ],
            );
        });

        $this->notifications->complaintMarkedMisleadingOrSpam($complaint, $actor);
    }
}
