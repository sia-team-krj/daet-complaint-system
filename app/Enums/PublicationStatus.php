<?php

namespace App\Enums;

/**
 * The publication state of a privacy-gated complaint.
 *
 * A complaint in a category that names somebody (a member of personnel, a
 * business) is filed private. It reaches the public transparency register
 * only when the office has settled it, the information has been verified as
 * true, the Mayor's office has redacted the public copy, and the Mayor's
 * office has approved it. The handoff is automatic once the report is
 * resolved and verified. A complaint that is genuinely a privacy concern is
 * declined and stays private for good.
 */
enum PublicationStatus: string
{
    /** The owning office has asked the Mayor's office to publish it. */
    case Pending = 'pending';

    /** The Mayor's office approved it. The redacted version is published. */
    case Approved = 'approved';

    /** The Mayor's office declined it. It is never published. */
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('statuses.publication_status.pending'),
            self::Approved => __('statuses.publication_status.approved'),
            self::Declined => __('statuses.publication_status.declined'),
        };
    }
}
