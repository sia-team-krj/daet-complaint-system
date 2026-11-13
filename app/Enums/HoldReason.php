<?php

namespace App\Enums;

/**
 * Why an office cannot get on with a complaint right now.
 *
 * A complaint can be perfectly legitimate, verified, and still not be
 * something the office can finish today — the asphalt is not in stock, the
 * budget for it has not been released, the work belongs to another body.
 * Those are not failures and they are not excuses to leave a resident
 * guessing; they are reasons the office records, with the date it expects
 * to be able to carry on.
 *
 * This is a marker on a complaint, not a status: the work has not stopped,
 * it is paused, and the status stays where it was.
 */
enum HoldReason: string
{
    /** The money for the work has not been released. */
    case AwaitingFunds = 'awaiting_funds';

    /** The materials or supplies for the work are not available. */
    case AwaitingMaterials = 'awaiting_materials';

    /** The item has to be bought or contracted first. */
    case AwaitingProcurement = 'awaiting_procurement';

    /** The work is waiting on a budget decision. */
    case AwaitingBudgetApproval = 'awaiting_budget_approval';

    /** The work belongs to another office or body. */
    case AwaitingOtherOffice = 'awaiting_other_office';

    /** The weather makes the work impossible for now. */
    case AwaitingWeather = 'awaiting_weather';

    /** Something else — the office says what, in its own words. */
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingFunds => __('statuses.hold_reason.awaiting_funds'),
            self::AwaitingMaterials => __('statuses.hold_reason.awaiting_materials'),
            self::AwaitingProcurement => __('statuses.hold_reason.awaiting_procurement'),
            self::AwaitingBudgetApproval => __('statuses.hold_reason.awaiting_budget_approval'),
            self::AwaitingOtherOffice => __('statuses.hold_reason.awaiting_other_office'),
            self::AwaitingWeather => __('statuses.hold_reason.awaiting_weather'),
            self::Other => __('statuses.hold_reason.other'),
        };
    }

    /**
     * Whether the reason needs the office to write what it means in its own
     * words. "Other" is the only one that cannot explain itself.
     */
    public function needsExplanation(): bool
    {
        return $this === self::Other;
    }
}
