<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * A cheap fingerprint of what a queue is showing.
 *
 * The live queue polls the server and compares this string with the one it
 * rendered: equal means nothing changed, so the DOM is left alone. It is built
 * from the rows on screen (their id, when they last changed and their state)
 * plus the size of the whole result, so a new case, a moved case or a case
 * whose status changed all show up.
 */
final class QueueFingerprint
{
    public static function for(LengthAwarePaginator $paginator): string
    {
        $rows = $paginator->getCollection()
            ->map(fn ($complaint): string => implode(':', [
                (string) $complaint->getKey(),
                (string) ($complaint->updated_at?->getTimestamp() ?? 0),
                (string) $complaint->status,
                (string) $complaint->review_status,
                (string) ($complaint->confirmed_priority ?? ''),
                (string) ($complaint->department_id ?? ''),
            ]))
            ->all();

        return sha1($paginator->total().'|'.implode('|', $rows));
    }
}
