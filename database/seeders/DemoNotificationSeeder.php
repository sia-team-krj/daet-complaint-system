<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo notifications so the bell, dropdown feed and full page can be looked at
 * without having to drive the whole verification workflow by hand.
 *
 * Every notification points at a complaint that already exists and every
 * information request is real, so nothing here can disagree with the state the
 * rest of the demo data is in. Re-running is safe: rows are matched on their
 * natural key rather than duplicated.
 */
class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo notifications...');

        $admin = User::where('role', 'admin')->first();
        $pedro = User::where('email', 'pedro@gmail.com')->first();
        $jose = User::where('email', 'jose@gmail.com')->first();

        if ($admin === null || $pedro === null || $jose === null) {
            $this->command->warn('  Skipped: run UserSeeder first.');

            return;
        }

        // The seeded department staff hold can_verify, so they are the ones who
        // would realistically receive verification work.
        $verifier = User::where('role', 'staff')->where('can_verify', true)->first();

        $pothole = $this->complaint('Pothole near the market entrance');
        $flooded = $this->complaint('Flooded pedestrian path after rainfall');
        $streetlight = $this->complaint('Streetlight not working at the barangay hall');
        $noise = $this->complaint('Late-night noise near the public plaza');
        $spam = $this->complaint('FREE MONEY click here now!!!');

        $created = 0;

        if ($verifier !== null) {
            $created += $this->notify(
                $verifier,
                Notification::COMPLAINT_NEEDS_VERIFICATION,
                "{$flooded?->ticket_id} is waiting to be verified",
                $flooded,
                ['review_status' => 'pending'],
                minutesAgo: 12,
            );

            $created += $this->notify(
                $verifier,
                Notification::COMPLAINT_VERIFICATION_DELEGATED,
                "Verification of {$pothole?->ticket_id} was handed to you",
                $pothole,
                ['ticket_id' => $pothole?->ticket_id],
                minutesAgo: 95,
            );

            $created += $this->notify(
                $verifier,
                Notification::COMPLAINT_INFORMATION_PROVIDED,
                "The resident replied about {$streetlight?->ticket_id}",
                $streetlight,
                ['ticket_id' => $streetlight?->ticket_id, 'round' => 1],
                minutesAgo: 40,
                read: true,
            );

            $created += $this->notify(
                $verifier,
                Notification::COMPLAINT_INFORMATION_STALE,
                "{$spam?->ticket_id} has been waiting on the resident for 5 days",
                $spam,
                [
                    'ticket_id' => $spam?->ticket_id,
                    'days' => 5,
                    // Matches the sweep command's dedupe key so re-running the
                    // seeder does not collide with a real sweep.
                    'request_id' => $this->openRequestFor($spam, $verifier)?->id,
                ],
                minutesAgo: 60 * 24 * 3,
            );
        }

        $created += $this->notify(
            $pedro,
            Notification::COMPLAINT_STATUS_CHANGED,
            "{$noise?->ticket_id} is now Resolved",
            $noise,
            ['ticket_id' => $noise?->ticket_id, 'status' => 'Resolved', 'review_status' => 'verified'],
            minutesAgo: 25,
        );

        $created += $this->notify(
            $pedro,
            Notification::COMPLAINT_STATUS_CHANGED,
            "{$pothole?->ticket_id} is now In Progress",
            $pothole,
            ['ticket_id' => $pothole?->ticket_id, 'status' => 'In Progress', 'review_status' => 'verified'],
            minutesAgo: 300,
            read: true,
        );

        $created += $this->notify(
            $jose,
            Notification::COMPLAINT_INFORMATION_REQUESTED,
            "The department needs more information about {$streetlight?->ticket_id}",
            $streetlight,
            [
                'ticket_id' => $streetlight?->ticket_id,
                'round' => 1,
                'question' => 'The streetlight photo is too dark to read the pole number. Please send a closer shot.',
            ],
            minutesAgo: 55,
        );

        $created += $this->notify(
            $admin,
            Notification::COMPLAINT_NEEDS_VERIFICATION,
            "{$spam?->ticket_id} was flagged and is waiting to be verified",
            $spam,
            ['review_status' => 'pending'],
            minutesAgo: 8,
        );

        $created += $this->notify(
            $admin,
            Notification::COMPLAINT_INFORMATION_STALE,
            "{$flooded?->ticket_id} has been waiting on the resident for 4 days",
            $flooded,
            ['ticket_id' => $flooded?->ticket_id, 'days' => 4, 'request_id' => null],
            minutesAgo: 60 * 24 * 2,
            read: true,
        );

        $this->command->info("Demo notifications completed ({$created} created).");
    }

    private function complaint(string $title): ?Complaint
    {
        return Complaint::where('title', $title)->latest('id')->first();
    }

    /**
     * Create one notification, or return false if this demo row already exists.
     */
    private function notify(
        User $recipient,
        string $type,
        string $title,
        ?Complaint $subject,
        array $context,
        int $minutesAgo,
        bool $read = false,
    ): int {
        if ($subject === null) {
            return 0;
        }

        $existing = Notification::where('user_id', $recipient->id)
            ->where('type', $type)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->id)
            ->first();

        if ($existing !== null) {
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

        // Backdated so the feed shows a believable spread rather than five
        // notifications stamped in the same second.
        $notification->forceFill([
            'created_at' => now()->subMinutes($minutesAgo),
            'updated_at' => now()->subMinutes($minutesAgo),
            'read_at' => $read ? now()->subMinutes(max(1, intdiv($minutesAgo, 2))) : null,
        ])->save();

        return 1;
    }

    /**
     * A genuine open request, so the "waiting on resident" filter and the
     * overdue treatment in the queue have something real to point at.
     */
    private function openRequestFor(?Complaint $complaint, User $verifier): ?ComplaintInformationRequest
    {
        if ($complaint === null) {
            return null;
        }

        $existing = ComplaintInformationRequest::where('complaint_id', $complaint->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $request = ComplaintInformationRequest::create([
            'complaint_id' => $complaint->id,
            'requested_by' => $verifier->id,
            'question' => 'The photo is too dark to identify the damaged part. Please resend in daylight.',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);

        $request->forceFill(['created_at' => now()->subDays(5)])->save();

        if ($complaint->review_status !== 'needs_information') {
            $complaint->update(['review_status' => 'needs_information']);
        }

        return $request;
    }
}
