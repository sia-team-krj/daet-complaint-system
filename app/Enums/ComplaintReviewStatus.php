<?php

namespace App\Enums;

enum ComplaintReviewStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case NeedsInformation = 'needs_information';
    case Duplicate = 'duplicate';
    case Rejected = 'rejected';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending verification',
            self::Verified => 'Verified',
            self::NeedsInformation => 'Needs information',
            self::Duplicate => 'Duplicate',
            self::Rejected => 'Rejected',
            self::Escalated => 'Escalated',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'review-pending',
            self::Verified => 'review-verified',
            self::NeedsInformation => 'review-needs-information',
            self::Duplicate => 'review-duplicate',
            self::Rejected => 'review-rejected',
            self::Escalated => 'review-escalated',
        };
    }
}
