<?php

namespace App\Console\Commands;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Notification;
use App\Services\ComplaintUpdateDelivery;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reminds the resident that a report they filed was resolved.
 *
 * The office marks the work done and the case waits for the resident: they
 * confirm it, or they say it is not fixed. Without a reminder the only
 * people who know the case is waiting are the office — and seven days later
 * the system closes it for them, which reads as the office deciding the
 * resident was satisfied.
 *
 * So the resident is asked, before the grace period runs out, and only once:
 * a second reminder for the same report would be nagging.
 */
class RemindUnconfirmedResolutions extends Command
{
    protected $signature = 'complaints:remind-resolution
                            {--days=3 : Days after resolution before the resident is reminded}';

    protected $description = 'Remind residents to confirm a complaint the office has marked resolved.';

    public function handle(NotificationService $notifications, ComplaintUpdateDelivery $ticketUpdates): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $complaints = Complaint::query()
            ->where('status', ComplaintStatus::Resolved->value)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->with('user')
            ->get();

        if ($complaints->isEmpty()) {
            $this->info("No resolved complaint is waiting for the resident's confirmation.");

            return self::SUCCESS;
        }

        $reminded = 0;

        foreach ($complaints as $complaint) {
            // One reminder per report. The resident has had their answer; a
            // second one is nagging.
            $alreadyReminded = Notification::query()
                ->where('type', Notification::COMPLAINT_RESOLUTION_REMINDER)
                ->where('subject_type', $complaint->getMorphClass())
                ->where('subject_id', $complaint->id)
                ->exists();

            if ($alreadyReminded) {
                continue;
            }

            try {
                $notifications->resolutionReminder($complaint);
                $ticketUpdates->remindResolution($complaint, $days);
                $reminded++;
            } catch (Throwable $exception) {
                report($exception);
                $this->warn("Failed to remind {$complaint->ticket_id}: {$exception->getMessage()}");
            }
        }

        $this->info("Reminded {$reminded} resident(s) to confirm a resolved complaint.");

        return self::SUCCESS;
    }
}
