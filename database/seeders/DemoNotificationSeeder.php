<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Notifications for the presentation cast: a verifier with a queue, field
 * staff with work waiting, the Mayor's Office with a publication request,
 * and residents with updates and asks on their own tickets.
 *
 * Every notification points at a real seeded complaint, so nothing in the
 * bell disagrees with the state of the data it names.
 */
class DemoNotificationSeeder extends Seeder
{
    private function complaintByTitle(string $title): ?Complaint
    {
        return Complaint::where('title', $title)->first();
    }

    public function run(): void
    {
        $this->command->info('Seeding demo notifications...');

        $admin = User::where('role', 'admin')->first();
        $verifier = User::where('email', 'verifier@daetlistens.gov.ph')->first();
        $juan = User::where('email', 'engineering@daetlistens.gov.ph')->first();
        $ana = User::where('email', 'mayor@daetlistens.gov.ph')->first();
        $carlos = User::where('email', 'menro@daetlistens.gov.ph')->first();

        $pedro = User::where('email', 'pedro@gmail.com')->first();
        $nena = User::where('email', 'nena@gmail.com')->first();
        $jose = User::where('email', 'jose@gmail.com')->first();
        $maria = User::where('email', 'maria.santos@gmail.com')->first();
        $carmen = User::where('email', 'carmen.delarosa@gmail.com')->first();

        $streetlight = $this->complaintByTitle('Streetlight out at the barangay hall waiting area');
        $spam = $this->complaintByTitle('FREE MONEY!!! click here now!!!');
        $pothole = $this->complaintByTitle('Pothole along the national road near the Salcedo junction');
        $flooded = $this->complaintByTitle('Flooded pedestrian lane after heavy rain in Pandan');
        $garbage = $this->complaintByTitle('Hindi kinokolekta ang basura sa tabi ng health center');
        $plasticApproved = $this->complaintByTitle('Sari-sari store along the national highway using single-use plastics');
        $plasticPending = $this->complaintByTitle('Dry goods store in Mantagbac giving out plastic sachets and bags');
        $overdueRoad = $this->complaintByTitle('Malaking butas sa national highway malapit sa waiting shed');
        $holdRoad = $this->complaintByTitle('Uneven pavement and depression on the Bagasbas feeder road');
        $water = $this->complaintByTitle('Matagal nang hindi umaandar ang suplay ng tubig ng bayan sa Cobangbang');

        $created = 0;

        // ── Verifier (Engineering): two tickets waiting on verification ──
        $created += $this->notify($verifier, Notification::COMPLAINT_NEEDS_VERIFICATION,
            "{$streetlight?->ticket_id} is waiting to be verified", $streetlight,
            ['review_status' => 'pending'], minutesAgo: 60 * 20);
        $created += $this->notify($admin, Notification::COMPLAINT_NEEDS_VERIFICATION,
            "{$spam?->ticket_id} was flagged and is waiting to be verified", $spam,
            ['review_status' => 'pending'], minutesAgo: 60 * 3);
        $created += $this->notify($verifier, Notification::COMPLAINT_INFORMATION_PROVIDED,
            "The resident replied about {$pothole?->ticket_id}", $pothole,
            ['ticket_id' => $pothole?->ticket_id, 'round' => 1], minutesAgo: 60 * 24 * 6, read: true);

        // ── Engineering field staff: overdue work and a fresh assignment ──
        $created += $this->notify($juan, Notification::COMPLAINT_WORK_OVERDUE,
            "{$overdueRoad?->ticket_id} is overdue — pothole repair past its target date", $overdueRoad,
            ['ticket_id' => $overdueRoad?->ticket_id, 'days_overdue' => 4], minutesAgo: 60 * 2);
        $created += $this->notify($admin, Notification::COMPLAINT_WORK_OVERDUE,
            "{$overdueRoad?->ticket_id} is overdue and still unassigned crew-wise", $overdueRoad,
            ['ticket_id' => $overdueRoad?->ticket_id], minutesAgo: 60 * 24 * 2, read: true);
        $created += $this->notify($juan, Notification::COMPLAINT_ASSIGNED,
            "You were assigned {$holdRoad?->ticket_id}", $holdRoad,
            ['ticket_id' => $holdRoad?->ticket_id], minutesAgo: 60 * 24 * 22, read: true);

        // ── MENRO: an answer overdue from the resident on the open request ──
        $created += $this->notify($carlos, Notification::COMPLAINT_INFORMATION_STALE,
            "{$garbage?->ticket_id} has been waiting on the resident for 5 days", $garbage,
            ['ticket_id' => $garbage?->ticket_id, 'days' => 5], minutesAgo: 60);

        // ── Residents: updates on their own tickets ──
        $created += $this->notify($pedro, Notification::COMPLAINT_STATUS_CHANGED,
            "{$overdueRoad?->ticket_id} is now In Progress", $overdueRoad,
            ['ticket_id' => $overdueRoad?->ticket_id, 'status' => 'In Progress', 'review_status' => 'verified'],
            minutesAgo: 60 * 24 * 10, read: true);
        $created += $this->notify($pedro, Notification::COMPLAINT_INFORMATION_REQUESTED,
            "The department needs more information about {$garbage?->ticket_id}", $garbage,
            ['ticket_id' => $garbage?->ticket_id, 'round' => 1], minutesAgo: 60 * 24 * 5, read: true);
        $created += $this->notify($nena, Notification::COMPLAINT_STATUS_CHANGED,
            "{$flooded?->ticket_id} is now Resolved", $flooded,
            ['ticket_id' => $flooded?->ticket_id, 'status' => 'Resolved', 'review_status' => 'verified'],
            minutesAgo: 60 * 24 * 29, read: true);
        $created += $this->notify($jose, Notification::COMPLAINT_INFORMATION_REQUESTED,
            "The department needs more information about {$pothole?->ticket_id}", $pothole,
            ['ticket_id' => $pothole?->ticket_id, 'round' => 1], minutesAgo: 60 * 24 * 7, read: true);
        $created += $this->notify($maria, Notification::COMPLAINT_PUT_ON_HOLD,
            "{$holdRoad?->ticket_id} is on hold — waiting for asphalt materials, expected November 20", $holdRoad,
            ['ticket_id' => $holdRoad?->ticket_id, 'hold_reason' => 'awaiting_materials', 'expected_resume_at' => '2026-11-20'],
            minutesAgo: 60 * 24 * 6, read: true);
        $created += $this->notify($carmen, Notification::COMPLAINT_STATUS_CHANGED,
            "{$water?->ticket_id} is now Under Review", $water,
            ['ticket_id' => $water?->ticket_id, 'status' => 'Under Review', 'review_status' => 'verified'],
            minutesAgo: 60 * 24 * 4, read: true);

        // ── Mayor's Office: a publication decision waiting on Ana ──
        $created += $this->notify($ana, Notification::COMPLAINT_PUBLICATION_REQUESTED,
            "MENRO asked to publish {$plasticPending?->ticket_id}", $plasticPending,
            ['ticket_id' => $plasticPending?->ticket_id, 'category' => 'plastic_usage'],
            minutesAgo: 60 * 24 * 2);
        $created += $this->notify($ana, Notification::COMPLAINT_PUBLICATION_APPROVED,
            "Public release of {$plasticApproved?->ticket_id} approved", $plasticApproved,
            ['ticket_id' => $plasticApproved?->ticket_id], minutesAgo: 60 * 24 * 8, read: true);

        $this->command->info("Demo notifications completed ({$created} created).");
    }

    /**
     * Create one notification, backdated so the feed shows a believable
     * spread. Returns 1 if created, 0 if skipped.
     */
    private function notify(
        ?User $recipient,
        string $type,
        string $title,
        ?Complaint $subject,
        array $context,
        int $minutesAgo,
        bool $read = false,
    ): int {
        if ($recipient === null || $subject === null) {
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
