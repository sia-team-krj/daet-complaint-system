<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Conversation-based follow-ups/messages on a single complaint.
 *
 * A follow-up does NOT create a new complaint or ticket. It adds a message to
 * the original complaint's conversation thread. Resident follow-ups on
 * Resolved/Closed complaints move the complaint back to Under Review.
 */
class ComplaintFollowUpService
{
    public function canPostMessage(Complaint $complaint, User $actor): bool
    {
        if ($actor->role === 'citizen') {
            return (int) $complaint->user_id === (int) $actor->id;
        }

        if ($actor->isDepartmentStaff()) {
            return (int) $complaint->department_id === (int) $actor->department_id;
        }

        return $actor->role === 'admin';
    }

    /**
     * @param array<int, string> $imagePaths
     */
    public function createMessage(Complaint $complaint, User $actor, string $body, array $imagePaths = []): ComplaintMessage
    {
        return DB::transaction(function () use ($complaint, $actor, $body, $imagePaths) {
            $senderType = match (true) {
                $actor->role === 'admin' => 'admin',
                $actor->isDepartmentStaff() => 'staff',
                default => 'resident',
            };

            $message = ComplaintMessage::create([
                'complaint_id' => $complaint->id,
                'user_id' => $actor->id,
                'sender_type' => $senderType,
                'body' => trim($body),
                'image_paths' => array_values($imagePaths),
            ]);

            // Resident follow-up on Resolved/Closed reopens for review.
            if ($senderType === 'resident' && in_array($complaint->status, [ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value], true)) {
                $previous = $complaint->status;
                $complaint->forceFill([
                    'status' => ComplaintStatus::UnderReview->value,
                    'resolved_at' => null,
                    'closed_at' => null,
                    'closed_by' => null,
                ])->save();

                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'actor_id' => $actor->id,
                    'previous_status' => $previous,
                    'new_status' => ComplaintStatus::UnderReview->value,
                    'comment' => 'Resident posted a follow-up message; complaint moved back to Under Review.',
                ]);
            }

            return $message;
        });
    }
}
