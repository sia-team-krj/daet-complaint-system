<?php

namespace App\Support;

use App\Models\Complaint;

/**
 * A cheap fingerprint of what a complaint is showing.
 *
 * The live ticket panel polls the server and compares this string with the one
 * it rendered: equal means nothing changed, so the page is left alone. It is
 * built from the complaint's own state and from the newest entry on its
 * timeline, so a status change, a review decision and a new note all show up
 * — while a comment that never touched the complaint does not.
 */
final class TicketFingerprint
{
    public static function for(Complaint $complaint): string
    {
        $newestLog = $complaint->logs->max('id');

        return sha1(implode(':', [
            (string) $complaint->getKey(),
            (string) ($complaint->updated_at?->getTimestamp() ?? 0),
            (string) $complaint->status,
            (string) $complaint->review_status,
            (string) ($complaint->verified_at?->getTimestamp() ?? 0),
            (string) ($newestLog ?? 0),
        ]));
    }
}
