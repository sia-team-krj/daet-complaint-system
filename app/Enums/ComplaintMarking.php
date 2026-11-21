<?php

namespace App\Enums;

/**
 * ComplaintMarking — the two ways office staff can mark a report misleading
 * or spam.
 *
 * The marking is the office's rejection of the report: the status moves to
 * ComplaintStatus::Rejected and this enum says which term the staff used.
 *
 * ALWAYS use this enum when reading or writing the marking value. Never use
 * raw strings directly in controllers or views — the label lives in
 * `statuses.complaint_marking.*`.
 */
enum ComplaintMarking: string
{
    /** The report misrepresents what the resident complained about. */
    case Misleading = 'misleading';

    /** The report is spam. */
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Misleading => __('statuses.complaint_marking.misleading'),
            self::Spam => __('statuses.complaint_marking.spam'),
        };
    }
}
