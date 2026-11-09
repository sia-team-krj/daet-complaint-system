<?php

namespace App\Console\Commands;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Closes resolved complaints the resident has not confirmed within 7 days.
 *
 * "Resolved" means the department finished the work; "Closed" means the case
 * is done. If the resident does not close it themselves, we close it
 * automatically after a grace period so reports do not stay open forever.
 */
class AutoCloseResolvedComplaints extends Command
{
    protected $signature = 'complaints:auto-close-resolved
                            {--days=7 : Days after resolution before auto-closing}';

    protected $description = 'Automatically close resolved complaints after 7 days without resident confirmation.';

    public function handle(NotificationService $notifications): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $complaints = Complaint::query()
            ->where('status', ComplaintStatus::Resolved->value)
            ->where(function ($query) use ($cutoff): void {
                $query->where('resolved_at', '<=', $cutoff)
                    ->orWhere(function ($legacy) use ($cutoff): void {
                        // Backfill fallback: complaints resolved before resolved_at
                        // existed. Use the log that recorded the resolution, else
                        // the last update time.
                        $legacy->whereNull('resolved_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->with('user')
            ->get();

        if ($complaints->isEmpty()) {
            $this->info("No resolved complaints due for auto-close ({$days} days).");

            return self::SUCCESS;
        }

        $closed = 0;

        foreach ($complaints as $complaint) {
            // Refine the legacy fallback: if we used updated_at, confirm there is
            // a Resolved log at or before the cutoff (or trust updated_at only as
            // a last resort) so we don't close something that just got touched.
            if ($complaint->resolved_at === null) {
                $resolvedLog = $complaint->logs()
                    ->where('new_status', ComplaintStatus::Resolved->value)
                    ->latest('created_at')
                    ->first();

                $resolvedAt = $resolvedLog?->created_at ?? $complaint->updated_at;

                if (! $resolvedAt || $resolvedAt->gt($cutoff)) {
                    continue;
                }
            }

            try {
                DB::transaction(function () use ($complaint, $days): void {
                    $complaint->refresh();

                    if ($complaint->statusEnum !== ComplaintStatus::Resolved) {
                        return;
                    }

                    $oldStatus = $complaint->statusEnum;

                    $complaint->update([
                        'status' => ComplaintStatus::Closed->value,
                        'closed_at' => now(),
                        'closed_by' => 'system',
                        'resolved_at' => $complaint->resolved_at ?? now()->subDays($days),
                    ]);

                    ComplaintLog::create([
                        'complaint_id' => $complaint->id,
                        'actor_id' => null,
                        'previous_status' => $oldStatus->value,
                        'new_status' => ComplaintStatus::Closed->value,
                        'comment' => "Automatically closed after {$days} days with no resident confirmation.",
                    ]);
                });

                $complaint->refresh();

                if ($complaint->statusEnum === ComplaintStatus::Closed) {
                    try {
                        $notifications->statusChanged(
                            $complaint,
                            ComplaintStatus::Closed->label(),
                            null,
                        );
                    } catch (Throwable $exception) {
                        report($exception);
                    }

                    $closed++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->warn("Failed to auto-close {$complaint->ticket_id}: {$exception->getMessage()}");
            }
        }

        $this->info("Auto-closed {$closed} resolved complaint(s) after {$days} days.");

        return self::SUCCESS;
    }
}
