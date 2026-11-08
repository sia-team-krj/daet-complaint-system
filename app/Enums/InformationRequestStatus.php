<?php

namespace App\Enums;

/**
 * InformationRequestStatus — lifecycle of a verifier's ask to a resident.
 *
 * This is a lifecycle rather than a log line, which is why it gets its own
 * table instead of a complaint_logs row: somebody is accountable for closing
 * an open request, and "is anything still outstanding?" must be answerable
 * without scanning a log for a marker.
 */
enum InformationRequestStatus: string
{
    /** Asked, waiting on the resident. */
    case Open = 'open';

    /** The resident replied. A verifier still has to close it out. */
    case Answered = 'answered';

    /** Closed. Either the reply satisfied the verifier or the ask was withdrawn. */
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('statuses.information_request.open'),
            self::Answered => __('statuses.information_request.answered'),
            self::Resolved => __('statuses.information_request.resolved'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'information-request-open',
            self::Answered => 'information-request-answered',
            self::Resolved => 'information-request-resolved',
        };
    }

    public function isOutstanding(): bool
    {
        return $this !== self::Resolved;
    }
}
