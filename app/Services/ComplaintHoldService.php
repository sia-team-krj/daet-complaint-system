<?php

namespace App\Services;

use App\Enums\HoldReason;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Putting a complaint on hold, and taking it off again.
 *
 * A complaint can be perfectly legitimate, verified, and still not be
 * something the office can finish today: the asphalt is not in stock, the
 * budget has not been released, the work belongs to another body. None of
 * those are failures, and none of them are a reason to leave the resident
 * guessing.
 *
 * So the office records why, says it out loud to the resident, and the
 * deadline it owes pauses for exactly as long as it waits. The hold is a
 * marker, not a status: the work has not stopped, it is paused, and the
 * report does not move backwards in the resident's progress tracker.
 *
 * Everything that happens here is written to the audit trail, because a hold
 * that cannot be explained is indistinguishable from an office sitting on a
 * complaint.
 */
class ComplaintHoldService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Pause the work on a complaint.
     *
     * @param  array{hold_reason: string, hold_note: string, hold_internal_note?: string|null, hold_expected_resume_at?: string|null}  $input
     */
    public function putOnHold(Complaint $complaint, User $actor, array $input): void
    {
        // The work on a finished report (resolved, rejected, closed) cannot
        // be paused — there is nothing left to pause. A hold that was already
        // in place can still be taken off: the office clears it, never takes
        // a new one.
        if ($complaint->statusEnum->isClosed()) {
            throw ValidationException::withMessages([
                'hold' => 'There is no work left to pause on this report.',
            ]);
        }

        if ($complaint->isOnHold()) {
            throw ValidationException::withMessages([
                'hold' => 'This complaint is already on hold.',
            ]);
        }

        $reason = HoldReason::tryFrom((string) ($input['hold_reason'] ?? ''));

        if ($reason === null) {
            throw ValidationException::withMessages([
                'hold_reason' => 'Choose why the work is on hold.',
            ]);
        }

        $note = trim((string) ($input['hold_note'] ?? ''));

        // "Other" is the only reason that cannot explain itself.
        if ($reason->needsExplanation() && $note === '') {
            throw ValidationException::withMessages([
                'hold_note' => 'Say what the reason is, so the resident and the administrator can read it.',
            ]);
        }

        if ($note === '') {
            throw ValidationException::withMessages([
                'hold_note' => 'Tell the resident why the work is on hold.',
            ]);
        }

        $internalNote = trim((string) ($input['hold_internal_note'] ?? '')) ?: null;
        $expectedResume = $input['hold_expected_resume_at'] ?? null;

        DB::transaction(function () use ($complaint, $actor, $reason, $note, $internalNote, $expectedResume): void {
            $complaint->update([
                'hold_reason' => $reason->value,
                'hold_note' => $note,
                'hold_internal_note' => $internalNote,
                'hold_expected_resume_at' => $expectedResume !== null && $expectedResume !== ''
                    ? $expectedResume
                    : null,
                'hold_started_at' => now(),
                'hold_started_by' => $actor->id,
            ]);

            $complaint->refresh();

            // The resident reads this. It carries no status change of its
            // own, so the timeline shows it as an update rather than as the
            // report moving.
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'previous_status' => null,
                'new_status' => null,
                'comment' => $note,
                'actor_id' => $actor->id,
                'is_internal' => false,
            ]);

            if ($internalNote !== null) {
                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'previous_status' => null,
                    'new_status' => null,
                    'comment' => $internalNote,
                    'actor_id' => $actor->id,
                    'is_internal' => true,
                ]);
            }

            $this->activityLogger->log(
                'complaint.put_on_hold',
                "{$actor->full_name} put complaint {$complaint->ticket_id} on hold: {$reason->label()}.",
                $complaint,
                metadata: [
                    'hold_reason' => $reason->value,
                    'public_note' => $note,
                    'internal_note' => $internalNote,
                    'expected_resume_at' => $expectedResume !== null && $expectedResume !== '' ? $expectedResume : null,
                ],
            );
        });

        $this->notifications->complaintPutOnHold($complaint, $actor);
    }

    /**
     * Take the hold off and carry on with the work.
     */
    public function resume(Complaint $complaint, User $actor, ?string $note = null): void
    {
        if (! $complaint->isOnHold()) {
            throw ValidationException::withMessages([
                'hold' => 'This complaint is not on hold.',
            ]);
        }

        $reason = $complaint->holdReasonEnum();
        $heldMinutes = $complaint->heldMinutes();
        $publicNote = trim((string) ($note ?? '')) ?: null;

        DB::transaction(function () use ($complaint, $actor, $reason, $heldMinutes, $publicNote): void {
            $complaint->update([
                'hold_reason' => null,
                'hold_note' => null,
                'hold_internal_note' => null,
                'hold_expected_resume_at' => null,
                'hold_started_at' => null,
                'hold_started_by' => null,
                // The wait counts towards the office's deadline, so the
                // clock it owes does not move while it was paused.
                'hold_total_minutes' => (int) $complaint->hold_total_minutes + $heldMinutes,
            ]);

            $complaint->refresh();

            if ($publicNote !== null) {
                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'previous_status' => null,
                    'new_status' => null,
                    'comment' => $publicNote,
                    'actor_id' => $actor->id,
                    'is_internal' => false,
                ]);
            }

            $this->activityLogger->log(
                'complaint.hold_removed',
                "{$actor->full_name} took complaint {$complaint->ticket_id} off hold after "
                    .intdiv($heldMinutes, 1440).' day(s) on hold.',
                $complaint,
                metadata: [
                    'hold_reason' => $reason?->value,
                    'held_minutes' => $heldMinutes,
                    'public_note' => $publicNote,
                ],
            );
        });

        $this->notifications->complaintHoldRemoved($complaint, $actor);
    }
}
