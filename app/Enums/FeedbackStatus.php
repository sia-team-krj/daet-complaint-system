<?php

namespace App\Enums;

/**
 * Where a piece of feedback stands.
 *
 * A rating counts from the moment it is given. A suggestion waits for
 * somebody to read it — the same rule the public register already follows
 * for a complaint, so nothing a resident wrote goes out unreviewed.
 */
enum FeedbackStatus: string
{
    /** On the public register, and counted in the averages. */
    case Published = 'published';

    /** Written, and waiting for the administration to read it. */
    case Pending = 'pending';

    /** Read, and deliberately kept off the register. */
    case Withheld = 'withheld';

    public function label(): string
    {
        return match ($this) {
            self::Published => __('statuses.feedback_status.published'),
            self::Pending => __('statuses.feedback_status.pending'),
            self::Withheld => __('statuses.feedback_status.withheld'),
        };
    }
}
