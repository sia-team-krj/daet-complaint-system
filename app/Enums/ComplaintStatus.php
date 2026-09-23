<?php

namespace App\Enums;

/**
 * ComplaintStatus — PHP 8.1 backed enum
 *
 * ALWAYS use this enum when reading or writing complaint status values.
 * Never use raw strings like 'In Progress' directly in controllers or views.
 *
 * Usage examples:
 *   $complaint->status = ComplaintStatus::InProgress->value;
 *   $complaint->logStatus(ComplaintStatus::Resolved->value, auth()->id(), 'Fixed.');
 *   $complaint->statusEnum->label()
 *   $complaint->statusEnum->badgeColor()
 */
enum ComplaintStatus: string
{
    case Submitted = "Submitted";
    case UnderReview = "Under Review";
    case InProgress = "In Progress";
    case Resolved = "Resolved";
    case Rejected = "Rejected";
    case Closed = "Closed";

    /**
     * Human-friendly label for display in Blade views.
     */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => "Submitted",
            self::UnderReview => "Under Review",
            self::InProgress => "In Progress",
            self::Resolved => "Resolved",
            self::Rejected => "Rejected",
            self::Closed => "Closed",
        };
    }

    /**
     * Tailwind CSS classes for status badge pills in Blade templates.
     *
     * Usage in Blade:
     *   <span class="badge {{ $complaint->statusEnum->badgeColor() }}">
     *       {{ $complaint->statusEnum->label() }}
     *   </span>
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Submitted => "bg-gray-100 text-gray-600",
            self::UnderReview => "bg-yellow-100 text-yellow-700",
            self::InProgress => "bg-blue-100 text-blue-700",
            self::Resolved => "bg-green-100 text-green-700",
            self::Rejected => "bg-red-100 text-red-700",
            self::Closed => "bg-slate-100 text-slate-500",
        };
    }

    /**
     * Whether this status means the complaint is still active/open.
     */
    public function isOpen(): bool
    {
        return in_array($this, [
            self::Submitted,
            self::UnderReview,
            self::InProgress,
        ]);
    }

    /**
     * Whether this status means the complaint is finished.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Rejected, self::Closed]);
    }

    /**
     * Valid next statuses from the current status.
     * Enforces the correct workflow — staff cannot skip steps or go backward
     * except where explicitly allowed.
     *
     * @return ComplaintStatus[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::InProgress, self::Rejected],
            self::InProgress => [self::Resolved, self::Rejected],
            self::Resolved => [self::Closed],
            self::Rejected => [], // terminal — no further transitions
            self::Closed => [], // terminal — no further transitions
        };
    }

    /**
     * Check if transitioning to a given status is allowed from this status.
     *
     * Usage:
     *   if ($complaint->statusEnum->canTransitionTo(ComplaintStatus::Resolved)) { ... }
     */
    public function canTransitionTo(ComplaintStatus $next): bool
    {
        return in_array($next, $this->allowedTransitions());
    }
}
