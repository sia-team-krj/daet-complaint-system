<?php

namespace App\Enums;

/**
 * WorkExecutionMode — what kind of work a complaint actually is.
 *
 * The office cannot photograph an enforcement action, and it cannot settle a
 * repair with a sentence. This says which is which, so the system can demand
 * the right proof instead of demanding one kind of proof for everything.
 *
 * The mode is resolved from the complaint's category (see
 * config/complaint_work_policy.php) and frozen onto the complaint the first
 * time anyone acts on it, so changing the policy later never changes the
 * rules on a ticket that is already in flight.
 */
enum WorkExecutionMode: string
{
    /**
     * Physical remediation. Somebody has to turn up and do the work, so the
     * work is proven by a photograph of it.
     */
    case LaborRequired = 'labor_required';

    /**
     * Enforcement, regulatory or desk work. There is no site work to
     * photograph, so a written record of the action is what counts.
     */
    case ReportOnly = 'report_only';

    /**
     * Handed to another body. What counts is the referral itself — who took
     * it, and the reference number.
     */
    case Referral = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::LaborRequired => __('statuses.work_mode.labor_required'),
            self::ReportOnly => __('statuses.work_mode.report_only'),
            self::Referral => __('statuses.work_mode.referral'),
        };
    }

    /**
     * Whether this kind of work is proven by a photograph of the site.
     */
    public function requiresSitePhoto(): bool
    {
        return $this === self::LaborRequired;
    }

    /**
     * Whether every action still needs a written record. It always does: a
     * photograph shows a place, a sentence says what was done.
     */
    public function requiresWrittenRecord(): bool
    {
        return true;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LaborRequired => 'work-mode-labor',
            self::ReportOnly => 'work-mode-report',
            self::Referral => 'work-mode-referral',
        };
    }
}
