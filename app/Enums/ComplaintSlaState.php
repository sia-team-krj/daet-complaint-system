<?php

namespace App\Enums;

/**
 * ComplaintSlaState — where a complaint stands against its deadline.
 *
 * The office owes work the moment it verifies a report. These states say how
 * that obligation is going, so a ticket that nobody started does not sit in
 * the queue looking exactly like a ticket that is being worked on today.
 */
enum ComplaintSlaState: string
{
    /** Comfortably inside the deadline. */
    case OnTrack = 'on_track';

    /** The deadline is today or tomorrow. */
    case DueSoon = 'due_soon';

    /** The deadline has passed and the work has not started, or has not finished. */
    case Overdue = 'overdue';

    /** The work was done inside the deadline. */
    case Met = 'met';

    /** Nothing is owed yet — the report has not been verified. */
    case NotApplicable = 'not_applicable';

    /**
     * The office has paused the work — waiting on funds, materials, a budget
     * decision, another body. The clock is paused with it, so the wait is
     * not counted against the office; the administrator still sees how long
     * it has been waiting.
     */
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::OnTrack => __('statuses.sla.on_track'),
            self::DueSoon => __('statuses.sla.due_soon'),
            self::Overdue => __('statuses.sla.overdue'),
            self::Met => __('statuses.sla.met'),
            self::NotApplicable => __('statuses.sla.not_applicable'),
            self::OnHold => __('statuses.sla.on_hold'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OnTrack => 'sla-on-track',
            self::DueSoon => 'sla-due-soon',
            self::Overdue => 'sla-overdue',
            self::Met => 'sla-met',
            self::NotApplicable => 'sla-none',
            self::OnHold => 'sla-on-hold',
        };
    }
}
