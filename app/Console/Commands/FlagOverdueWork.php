<?php

namespace App\Console\Commands;

use App\Enums\ComplaintSlaState;
use App\Models\Complaint;
use App\Models\Notification;
use App\Services\ComplaintSlaService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Raises a notification for every complaint whose work has slipped past its
 * deadline.
 *
 * The queue already shows how long the office has left, but a badge that
 * nobody refreshes is not a safety net. This exists so the rot has to be
 * actively ignored rather than simply unnoticed.
 *
 * A complaint is only raised once. Re-notifying the same office every day
 * would train people to ignore the badge, so each complaint is keyed on its id
 * in the payload — the subject stays the complaint so the link lands
 * correctly, but the sweep can recognise it already warned about this one.
 */
class FlagOverdueWork extends Command
{
    protected $signature = 'complaints:flag-overdue-work';

    protected $description = 'Notify departments about verified work that has slipped past its deadline.';

    public function handle(ComplaintSlaService $sla, NotificationService $notifications): int
    {
        $overdueIds = $sla->overdueIds();

        if ($overdueIds === []) {
            $this->info('No verified complaint has slipped past its deadline.');

            return self::SUCCESS;
        }

        $raised = 0;

        foreach (Complaint::whereIn('id', $overdueIds)->with('department')->get() as $complaint) {
            $summary = $sla->summary($complaint);

            if ($summary['state'] !== ComplaintSlaState::Overdue) {
                continue;
            }

            $daysOverdue = abs((int) $summary['days']);

            $alreadyRaised = Notification::query()
                ->where('type', Notification::COMPLAINT_WORK_OVERDUE)
                ->whereJsonContains('context->ticket_id', $complaint->ticket_id)
                ->exists();

            if ($alreadyRaised) {
                continue;
            }

            $raised += $notifications->workOverdue($complaint, $daysOverdue);
        }

        $this->info("Raised {$raised} notification(s) across ".count($overdueIds)." overdue complaint(s).");

        return self::SUCCESS;
    }
}
