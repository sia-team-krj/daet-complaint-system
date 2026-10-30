<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Raises a notification for every information request that has gone unanswered.
 *
 * The queue already shows how long a resident has been waiting, but a badge
 * that nobody refreshes is not a safety net. This exists so the rot has to be
 * actively ignored rather than simply unnoticed. Auto-expiry was rejected on
 * purpose: quietly closing a request would discard the resident's obligation
 * without anyone deciding to.
 */
class FlagStaleInformationRequests extends Command
{
    protected $signature = 'complaints:flag-stale-requests
                            {--days= : Override how many days counts as stale}';

    protected $description = 'Notify department verifiers about resident requests left unanswered.';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) ($this->option('days') ?: NotificationService::STALE_AFTER_DAYS);

        $stale = $notifications->staleRequests(days: $days);

        if ($stale->isEmpty()) {
            $this->info("No information requests have been waiting more than {$days} days.");

            return self::SUCCESS;
        }

        $raised = 0;

        foreach ($stale as $request) {
            // Re-notifying the same verifiers every day would train people to
            // ignore the badge, so each request is only raised once. Keyed on the
            // request id in the payload rather than the subject, because the
            // subject is the complaint and a complaint can hold several asks
            // across its rounds.
            $alreadyRaised = \App\Models\Notification::query()
                ->where('type', \App\Models\Notification::COMPLAINT_INFORMATION_STALE)
                ->whereJsonContains('context->request_id', $request->id)
                ->exists();

            if ($alreadyRaised) {
                continue;
            }

            $complaint = $request->complaint;

            if ($complaint === null) {
                continue;
            }

            $raised += $notifications->informationStale($complaint, $request);
        }

        $this->info("Raised {$raised} notification(s) across ".count($stale)." stale request(s).");

        return self::SUCCESS;
    }
}
