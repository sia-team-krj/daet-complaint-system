<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\ComplaintReopen;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ComplaintReopenService
{
    /**
     * Record a resident's reopen of a resolved complaint and return the
     * complaint to the verification process.
     *
     * The complaint row is never replaced or recreated — the same complaint
     * (and ticket id) is returned to Under Review. Previous resolution
     * timestamps are preserved; each reopen becomes its own ComplaintReopen
     * row and a public ComplaintLog entry so it shows up in the resident's
     * Updates timeline as activity history.
     */
    public function reopen(Complaint $complaint, User $resident, string $reason, NotificationService $notifications): ComplaintReopen
    {
        return DB::transaction(function () use ($complaint, $resident, $reason, $notifications): ComplaintReopen {
            $complaint->refresh();

            abort_if($complaint->statusEnum !== ComplaintStatus::Resolved, 403, __('complaint_show.reopen.not_available'));

            $reason = trim($reason);

            $reopen = ComplaintReopen::create([
                'complaint_id' => $complaint->id,
                'resident_id' => $resident->id,
                'reason' => $reason,
            ]);

            $complaint->forceFill([
                'status' => ComplaintStatus::UnderReview->value,
            ])->save();

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'actor_id' => $resident->id,
                'previous_status' => ComplaintStatus::Resolved->value,
                'new_status' => ComplaintStatus::UnderReview->value,
                'comment' => __('complaint_show.reopen.log_comment', ['reason' => $reason]),
                'is_internal' => false,
            ]);

            $notifications->notifyDepartmentVerifiers(
                $complaint,
                \App\Models\Notification::COMPLAINT_REOPENED,
                "{$complaint->ticket_id} is reopen requested by the resident",
                $resident,
                ['ticket_id' => $complaint->ticket_id],
            );

            return $reopen;
        });
    }
}
