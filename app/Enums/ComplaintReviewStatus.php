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
            self::Pending => __('statuses.review_status.pending'),
            self::Verified => __('statuses.review_status.verified'),
            self::NeedsInformation => __('statuses.review_status.needs_information'),
            self::Duplicate => __('statuses.review_status.duplicate'),
            self::Rejected => __('statuses.review_status.rejected'),
            self::Escalated => __('statuses.review_status.escalated'),
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
