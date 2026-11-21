<?php

namespace App\Services;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use App\Services\ComplaintPriorityService;
use App\Services\DepartmentRouter;
use Throwable;

/**
 * ComplaintFollowUpService — a finished complaint may accept a sub-ticket
 * from its owner: a follow-up filed when the resident is not satisfied, or when
 * the same issue persists after the report was resolved and closed.
 *
 * The follow-up is a child of the complaint it follows. It is its own ticket
 * (own status, own SLA) seeded from the parent — same category, same location —
 * and it receives its own ticket number. The parent itself stays closed.
 */
class ComplaintFollowUpService
{
    /**
     * May the owner of this finished complaint file a follow-up?
     *
     * A follow-up is only possible once the complaint is finished (resolved or
     * closed) and nothing is already waiting on the resident from a previous
     * follow-up — one follow-up at a time.
     */
    public function canFile(Complaint $parent): bool
    {
        if (! in_array($parent->status, [
            ComplaintStatus::Resolved->value,
            ComplaintStatus::Closed->value,
        ], true)) {
            return false;
        }

        return $parent->openFollowUp() === null;
    }

    /**
     * File the follow-up: a new complaint childed to the parent.
     *
     * @param array<string, mixed> $validated
     * @param array<int, string> $imagePaths
     */
    public function create(Complaint $parent, array $validated, User $actor, array $imagePaths = []): Complaint
    {
        $followUp = Complaint::create([
            'user_id' => $actor->id,
            'department_id' => DepartmentRouter::resolve($parent->category)?->id,
            'parent_id' => $parent->id,
            'category' => $parent->category,
            'title' => (string) ($validated['title'] ?? ''),
            'description' => (string) ($validated['description'] ?? ''),
            'image_path' => $imagePaths[0] ?? null,
            'image_paths' => $imagePaths,
            'latitude' => $parent->latitude,
            'longitude' => $parent->longitude,
            'address_text' => $parent->address_text,
            'barangay' => $parent->barangay,
            'intake_mode' => $parent->intake_mode ?? 'physical_required',
            'urgency' => $parent->urgency ?? 'Medium',
            'status' => ComplaintStatus::Submitted->value,
            'review_status' => ComplaintReviewStatus::Pending->value,
            'is_public' => (bool) $parent->is_public,
            'named_party' => $parent->named_party,
            'named_party_position' => $parent->named_party_position,
        ]);

        $suggestion = app(ComplaintPriorityService::class)->suggest($followUp);
        $followUp->update([
            'suggested_priority' => $suggestion['priority'],
            'suggestion_reasons' => $suggestion['reasons'],
        ]);

        ComplaintLog::create([
            'complaint_id' => $followUp->id,
            'actor_id' => $actor->id,
            'previous_status' => null,
            'new_status' => ComplaintStatus::Submitted->value,
            'comment' => 'Follow-up filed against complaint '.$parent->ticket_id.' by '.$actor->full_name.'.',
        ]);

        return $followUp->fresh();
    }
}
