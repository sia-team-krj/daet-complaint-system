<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Submitted    = 'Submitted';
    case UnderReview  = 'Under Review';
    case InProgress   = 'In Progress';
    case Resolved     = 'Resolved';
    case Rejected     = 'Rejected';
    case Closed       = 'Closed';

    /**
     * Returns a Tailwind CSS class string for badge styling.
     * Used in Blade: class="{{ $status->badgeColor() }}"
     */
    public function badgeColor(): string
    {
        return match($this) {
            self::Submitted   => 'badge-submitted',
            self::UnderReview => 'badge-review',
            self::InProgress  => 'badge-progress',
            self::Resolved    => 'badge-resolved',
            self::Rejected    => 'badge-rejected',
            self::Closed      => 'badge-closed',
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}