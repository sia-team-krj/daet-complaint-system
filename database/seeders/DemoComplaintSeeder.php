<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use App\Services\ComplaintDetectionService;
use App\Services\DepartmentRouter;
use Illuminate\Database\Seeder;

class DemoComplaintSeeder extends Seeder
{
    public function __construct(private readonly ComplaintDetectionService $detector)
    {
    }

    public function run(): void
    {
        $this->command->info('Seeding demo complaints...');

        $pedro = User::where('email', 'pedro@gmail.com')->firstOrFail();
        $nena = User::where('email', 'nena@gmail.com')->firstOrFail();
        $jose = User::where('email', 'jose@gmail.com')->firstOrFail();

        $complaints = [
            [
                'user' => $pedro,
                'category' => 'road_damage',
                'title' => 'Large pothole along the national road',
                'description' => 'A large pothole has opened beside the national road near the bus stop. Vehicles swerve dangerously and residents need a safer passage.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'High',
                'status' => ComplaintStatus::InProgress,
                'is_public' => true,
            ],
            [
                'user' => $nena,
                'category' => 'garbage',
                'title' => 'Uncollected garbage beside the health center',
                'description' => 'Garbage collection has not reached the health center for several days. The waste is attracting flies and creating an unhealthy public area.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'Medium',
                'status' => ComplaintStatus::Submitted,
                'is_public' => false,
            ],
            [
                'user' => $jose,
                'category' => 'streetlight',
                'title' => 'Streetlight not working at the barangay hall',
                'description' => 'The streetlight outside the barangay hall has been dark for a week. The area becomes unsafe when residents walk home after evening activities.',
                'address' => 'Barangay VIII, Daet, Camarines Norte',
                'urgency' => 'Medium',
                'status' => ComplaintStatus::UnderReview,
                'is_public' => false,
            ],
            [
                'user' => $pedro,
                'category' => 'noise_complaint',
                'title' => 'Late-night noise near the public plaza',
                'description' => 'Loud music continues past midnight near the public plaza, disturbing nearby residents during the working week.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'Low',
                'status' => ComplaintStatus::Resolved,
                'is_public' => true,
            ],
            [
                'user' => $nena,
                'category' => 'flooding',
                'title' => 'Flooded pedestrian path after rainfall',
                'description' => 'The pedestrian path beside the national road remains flooded after heavy rain, making it difficult for pedestrians and small vendors.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'High',
                'status' => ComplaintStatus::Submitted,
                'is_public' => true,
            ],
            [
                'user' => $jose,
                'category' => 'road_damage',
                'title' => 'Pothole near the market entrance',
                'description' => 'There is a large pothole near the market entrance that needs immediate attention before it becomes dangerous for motorcycles.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'High',
                'status' => ComplaintStatus::Submitted,
                'is_public' => false,
            ],
            [
                'user' => $pedro,
                'category' => 'road_damage',
                'title' => 'Pothole near the market entrance',
                'description' => 'A separate resident reports a large pothole at the market entrance. The location and issue appear related to the previous report.',
                'address' => 'Barangay VI, Daet, Camarines Norte',
                'urgency' => 'Medium',
                'status' => ComplaintStatus::Submitted,
                'is_public' => false,
            ],
            [
                'user' => $jose,
                'category' => 'others',
                'title' => 'FREE MONEY click here now!!!',
                'description' => 'FREE MONEY FREE MONEY click here https://spam.example.com guaranteed profit urgent wire transfer',
                'address' => 'Barangay I, Daet, Camarines Norte',
                'urgency' => 'Low',
                'status' => ComplaintStatus::Submitted,
                'is_public' => false,
            ],
        ];

        foreach ($complaints as $data) {
            $complaint = $this->createComplaint($data);
            $this->command->line(sprintf(
                '  %s | %s | %s | %s',
                $complaint->ticket_id,
                $data['category'],
                $complaint->is_public ? 'Public' : 'Private',
                $complaint->moderationLabel()
            ));
        }

        $this->command->info('Demo complaints completed.');
    }

    /**
     * @param array{user: User, category: string, title: string, description: string, address: string, urgency: string, status: ComplaintStatus, is_public: bool} $data
     */
    private function createComplaint(array $data): Complaint
    {
        $existing = Complaint::where('user_id', $data['user']->id)
            ->where('category', $data['category'])
            ->where('title', $data['title'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $department = DepartmentRouter::resolve($data['category']);
        $complaint = Complaint::create([
            'user_id' => $data['user']->id,
            'department_id' => $department?->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'address_text' => $data['address'],
            'latitude' => 14.1122,
            'longitude' => 122.9553,
            'urgency' => $data['urgency'],
            'status' => $data['status']->value,
            'is_public' => $data['is_public'],
            'suggested_priority' => in_array($data['category'], ['road_damage', 'flooding', 'sanitation'], true) ? 'elevated' : 'routine',
            'confirmed_priority' => in_array($data['category'], ['road_damage', 'flooding', 'sanitation'], true) ? 'elevated' : 'routine',
            'review_status' => 'verified',
            'reviewed_at' => now(),
        ]);

        ComplaintLog::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $data['user']->id,
            'previous_status' => null,
            'new_status' => $data['status']->value,
            'comment' => 'Demo complaint seeded for local testing.',
        ]);

        $moderation = $this->detector->evaluate($complaint);

        if ($complaint->isModerationFlagged()) {
            ActivityLog::create([
                'actor_id' => $data['user']->id,
                'actor_name' => $data['user']->full_name,
                'actor_role' => $data['user']->role,
                'department_id' => $complaint->department_id,
                'action' => 'complaint.moderation_flagged',
                'subject_type' => $complaint->getMorphClass(),
                'subject_id' => $complaint->id,
                'description' => "Demo complaint {$complaint->ticket_id} was flagged for staff review.",
                'metadata' => $moderation,
                'created_at' => now(),
            ]);
        }

        return $complaint;
    }
}
