<?php

namespace App\Enums;

/**
 * VerificationMethod — how a department settled whether a report is genuine.
 *
 * The distinction matters publicly: "we read this and it seems plausible" is a
 * weaker claim than "we attended and saw the condition". Publishing one vague
 * "verified" for both overstates what the LGU actually did.
 */
enum VerificationMethod: string
{
    /** Reviewed from the report alone. Nobody left the office. */
    case Desk = 'desk';

    /** Someone attended the site. Documentation is mandatory for this method. */
    case SiteVisit = 'site_visit';

    /** Already reported. Settled by the duplicate/fingerprint signal. */
    case DuplicateMatch = 'duplicate_match';

    public function label(): string
    {
        return match ($this) {
            self::Desk => __('statuses.verification_method.desk'),
            self::SiteVisit => __('statuses.verification_method.site_visit'),
            self::DuplicateMatch => __('statuses.verification_method.duplicate_match'),
        };
    }

    /** True when the claim implies somebody physically attended the site. */
    public function requiresSiteAttendance(): bool
    {
        return $this === self::SiteVisit;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Desk => 'verification-method-desk',
            self::SiteVisit => 'verification-method-site-visit',
            self::DuplicateMatch => 'verification-method-duplicate',
        };
    }
}
