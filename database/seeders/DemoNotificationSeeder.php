<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\FeedbackStatus;
use App\Models\Complaint;
use App\Models\ComplaintFeedback;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A small, curated set of notifications that point at real seeded complaints,
 * so the bell reflects the demo scenarios without depending on brittle titles.
 */
class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo notifications...');

        $admin = User::where('role', 'admin')->first();
        $verifier = User::where('email', 'verifier.eng@daetlistens.gov.ph')->first();
        $engStaff = User::where('email', 'staff.eng.1@daetlistens.gov.ph')->first();
        $menroStaff = User::where('email', 'staff.menro.1@daetlistens.gov.ph')->first();
        $pedro = User::where('email', 'aling@gmail.com')->first();

        $created = 0;

        // Pending review / flagged items for verifier or admin attention.
        $pendingReview = Complaint::query()
            ->where('review_status', 'pending')
            ->whereNull('duplicate_of_id')
            ->where('spam_status', 'clear')
            ->latest('id')
            ->first();
        $flaggedDuplicate = Complaint::query()->whereNotNull('duplicate_of_id')->latest('id')->first();
        $spamFlagged = Complaint::query()
            ->whereIn('spam_status', ['review', 'spam'])
            ->whereNull('duplicate_of_id')
            ->latest('id')
            ->first();

        $created += $this->notify($verifier, Notification::COMPLAINT_NEEDS_VERIFICATION,
            $pendingReview ? "{$pendingReview->ticket_id} is waiting to be verified" : null,
            $pendingReview, ['review_status' => 'pending'], 180);
        $created += $this->notify($admin, Notification::COMPLAINT_NEEDS_VERIFICATION,
            $flaggedDuplicate ? "{$flaggedDuplicate->ticket_id} was flagged as a possible duplicate" : null,
            $flaggedDuplicate, ['review_status' => 'pending', 'duplicate' => true], 120);
        $created += $this->notify($admin, Notification::COMPLAINT_NEEDS_VERIFICATION,
            $spamFlagged ? "{$spamFlagged->ticket_id} was flagged for moderation review" : null,
            $spamFlagged, ['spam_status' => $spamFlagged?->spam_status], 90);

        // Overdue work for Engineering staff.
        $overdue = Complaint::query()
            ->where('status', ComplaintStatus::InProgress->value)
            ->whereNotNull('verified_at')
            ->where('verified_at', '<=', now()->subDays(15))
            ->whereNull('hold_reason')
            ->latest('verified_at')
            ->first();
        $created += $this->notify($engStaff, Notification::COMPLAINT_WORK_OVERDUE,
            $overdue ? "{$overdue->ticket_id} is overdue and needs attention" : null,
            $overdue, ['ticket_id' => $overdue?->ticket_id], 60);

        // Resident notifications for Pedro: info request and resolved (can give feedback).
        $needsInfo = Complaint::query()
            ->where('user_id', $pedro?->id)
            ->where('review_status', 'needs_information')
            ->latest('id')
            ->first();
        $resolvedForFeedback = Complaint::query()
            ->where('user_id', $pedro?->id)
            ->where('status', ComplaintStatus::Resolved->value)
            ->whereDoesntHave('feedback')
            ->latest('id')
            ->first();

        $created += $this->notify($pedro, Notification::COMPLAINT_INFORMATION_REQUESTED,
            $needsInfo ? "The department needs more information about {$needsInfo->ticket_id}" : null,
            $needsInfo, ['ticket_id' => $needsInfo?->ticket_id, 'round' => 1], 60 * 24, read: false);
        $created += $this->notify($pedro, Notification::COMPLAINT_STATUS_CHANGED,
            $resolvedForFeedback ? "{$resolvedForFeedback->ticket_id} is now Resolved — you can rate the service" : null,
            $resolvedForFeedback, ['ticket_id' => $resolvedForFeedback?->ticket_id, 'status' => 'Resolved'], 60 * 20, read: false);

        // MENRO staff: duplicate/original context (original in progress).
        $original = $flaggedDuplicate?->duplicateOf;
        $created += $this->notify($menroStaff, Notification::COMPLAINT_ASSIGNED,
            $original ? "You are assigned to {$original->ticket_id}" : null,
            $original, ['ticket_id' => $original?->ticket_id], 60 * 24 * 2, read: true);

        $this->command->info("Demo notifications completed ({$created} created).");
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function notify(
        ?User $recipient,
        string $type,
        ?string $title,
        ?Complaint $subject,
        array $context,
        int $minutesAgo,
        bool $read = false,
    ): int {
        if ($recipient === null || $subject === null || $title === null) {
            return 0;
        }

        $exists = Notification::where('user_id', $recipient->id)
            ->where('type', $type)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->id)
            ->exists();

        if ($exists) {
            return 0;
        }

        $notification = Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'context' => array_filter($context, fn ($value) => $value !== null),
        ]);

        $notification->forceFill([
            'created_at' => now()->subMinutes($minutesAgo),
            'updated_at' => now()->subMinutes($minutesAgo),
            'read_at' => $read ? now()->subMinutes(max(1, intdiv($minutesAgo, 2))) : null,
        ])->save();

        return 1;
    }
}
