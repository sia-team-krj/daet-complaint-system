<?php

namespace Database\Seeders;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Services\ComplaintDetectionService;
use App\Services\DepartmentRouter;
use Illuminate\Database\Seeder;

class PreGeneratedComplaintSeeder extends Seeder
{
    public function __construct(private readonly ComplaintDetectionService $detector)
    {
    }

    public function run(): void
    {
        $this->command->info('Seeding pre-generated complaint tickets...');

        $citizens = User::where('role', 'citizen')->get();
        if ($citizens->isEmpty()) {
            $this->command->warn('No citizen users found; skipping.');

            return;
        }

        $staffByDept = User::where('role', 'staff')
            ->get()
            ->groupBy('department_id');

        $points = config('daet-barangays.points', []);

        $categories = ['road_damage', 'flooding', 'streetlight', 'garbage', 'sanitation', 'park_maintenance', 'business_permit', 'noise_complaint', 'stray_animals', 'others'];

        $titles = [
            'road_damage' => ['Large pothole on main road', 'Cracked pavement near the school', 'Road cave-in after heavy rain', 'Uneven road surface by the market'],
            'flooding' => ['Flooded street after downpour', 'Drainage clogged near the creek', 'Standing water on pedestrian lane', 'Flooded underpass after storm'],
            'streetlight' => ['Streetlight out on main road', 'Broken lamp post near the plaza', 'Dark corner by the barangay hall', 'Flickering light near the school'],
            'garbage' => ['Uncollected garbage by the health center', 'Overflowing bin near the market', 'Illegal dumping near the creek', 'Missed collection on our street'],
            'sanitation' => ['Clogged drainage near the market', 'Foul odor from sewer line', 'Blocked canal causing stagnant water', 'Dirty canal beside the road'],
            'park_maintenance' => ['Broken bench in the plaza', 'Overgrown grass in the park', 'Damaged playground equipment', 'Unlit path in the public park'],
            'business_permit' => ['Delayed business permit renewal', 'Unclear permit requirements', 'Long queue at the licensing office', 'Permit verification issue'],
            'noise_complaint' => ['Late-night noise near the plaza', 'Loud karaoke past midnight', 'Construction noise early morning', 'Barking dogs disturbing sleep'],
            'stray_animals' => ['Stray dogs near the school', 'Roaming cattle on the road', 'Stray cats around the market', 'Unattended animals in the park'],
            'others' => ['General concern for the barangay', 'Request for public assistance', 'Community safety concern', 'Miscellaneous municipal issue'],
        ];

        $descriptions = [
            'road_damage' => 'The road surface is damaged and poses a risk to motorists and pedestrians. Please inspect and repair as soon as possible.',
            'flooding' => 'Water accumulates after rain and blocks passage for residents. The drainage needs clearing and repair.',
            'streetlight' => 'The streetlight has been non-functional for several days, making the area unsafe at night.',
            'garbage' => 'Garbage has not been collected and is attracting pests. Please schedule collection promptly.',
            'sanitation' => 'The drainage is clogged and emits a foul odor. It needs immediate cleaning.',
            'park_maintenance' => 'The public area needs maintenance to keep it safe and usable for residents.',
            'business_permit' => 'I need assistance with my business permit application. The process has been delayed.',
            'noise_complaint' => 'Excessive noise is disturbing residents, especially late at night. Please look into this.',
            'stray_animals' => 'Stray animals are roaming freely and pose a safety risk to pedestrians.',
            'others' => 'This is a general concern that requires attention from the appropriate office.',
        ];

        $premadeResponses = [
            'Initial review started by the department.',
            'Field inspection scheduled for this week.',
            'Repair work has been assigned to a contractor.',
            'Additional information requested from the resident.',
            'Issue verified on site; remediation underway.',
            'Work completed; awaiting resident confirmation.',
            'Case closed after verification of resolution.',
            'No further action required at this time.',
        ];

        $internalNotes = [
            'Verified on site; photos attached.',
            'Resident provided additional evidence.',
            'Coordinated with the engineering crew.',
            'Marked as priority due to safety risk.',
            'Duplicate suspected; monitor closely.',
        ];

        $statuses = [
            ComplaintStatus::Submitted,
            ComplaintStatus::UnderReview,
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
            ComplaintStatus::Rejected,
            ComplaintStatus::Closed,
        ];

        $count = (int) (env('SEED_COMPLAINT_COUNT', 40));
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $citizen = $citizens->random();
            $category = $categories[array_rand($categories)];
            $department = DepartmentRouter::resolve($category);
            $barangay = $citizens->isNotEmpty()
                ? \App\Models\User::BARANGAYS[array_rand(\App\Models\User::BARANGAYS)]
                : 'Barangay VI (Centro)';

            $base = $points[$barangay] ?? [14.1122, 122.9553];
            $lat = $base[0] + (mt_rand(-30, 30) / 100000);
            $lng = $base[1] + (mt_rand(-30, 30) / 100000);

            $status = $statuses[array_rand($statuses)];
            $isPublic = (bool) mt_rand(0, 1);
            $urgency = ['Low', 'Medium', 'High'][array_rand(['Low', 'Medium', 'High'])];
            $priority = [ComplaintPriority::Routine->value, ComplaintPriority::Elevated->value, ComplaintPriority::Critical->value][array_rand([0, 1, 2])];

            $reviewStatus = match ($status) {
                ComplaintStatus::Submitted => ComplaintReviewStatus::Pending->value,
                ComplaintStatus::Rejected => ComplaintReviewStatus::Rejected->value,
                default => ComplaintReviewStatus::Verified->value,
            };

            $title = $titles[$category][array_rand($titles[$category])];
            $description = $descriptions[$category];

            $deptStaff = $department ? ($staffByDept[$department->id] ?? collect()) : collect();
            $assignedStaff = $deptStaff->isNotEmpty() && in_array($status, [ComplaintStatus::InProgress, ComplaintStatus::Resolved, ComplaintStatus::Closed], true)
                ? $deptStaff->random()
                : null;
            $verifyingStaff = $deptStaff->isNotEmpty() && $reviewStatus === ComplaintReviewStatus::Verified->value
                ? $deptStaff->random()
                : null;

            // Avoid self-verification conflict when possible
            if ($verifyingStaff && $assignedStaff && $verifyingStaff->id === $assignedStaff->id && $deptStaff->count() > 1) {
                $verifyingStaff = $deptStaff->where('id', '!=', $assignedStaff->id)->random();
            }

            $complaint = Complaint::create([
                'user_id' => $citizen->id,
                'department_id' => $department?->id,
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'address_text' => $barangay.', Daet, Camarines Norte',
                'barangay' => $barangay,
                'latitude' => $lat,
                'longitude' => $lng,
                'urgency' => $urgency,
                'status' => $status->value,
                'is_public' => $isPublic,
                'hidden_from_public' => false,
                'suggested_priority' => $priority,
                'confirmed_priority' => $priority,
                'review_status' => $reviewStatus,
                'reviewed_by' => $reviewStatus === ComplaintReviewStatus::Verified->value ? ($verifyingStaff?->id ?? null) : null,
                'reviewed_at' => $reviewStatus === ComplaintReviewStatus::Verified->value ? now()->subDays(rand(0, 14)) : null,
                'verification_method' => $reviewStatus === ComplaintReviewStatus::Verified->value ? VerificationMethod::SiteVisit->value : null,
                'assigned_staff_id' => $assignedStaff?->id,
                'verifying_staff_id' => $verifyingStaff?->id,
                'spam_status' => 'clear',
                'spam_score' => 0,
            ]);

            // Initial filing log
            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'actor_id' => $citizen->id,
                'previous_status' => null,
                'new_status' => ComplaintStatus::Submitted->value,
                'comment' => 'Complaint filed and routed to '.($department?->name ?? 'General Services Office').'.',
                'is_internal' => false,
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            // Status progression logs with premade responses
            $progression = [];
            if ($status === ComplaintStatus::UnderReview) {
                $progression = [ComplaintStatus::UnderReview];
            } elseif ($status === ComplaintStatus::InProgress) {
                $progression = [ComplaintStatus::UnderReview, ComplaintStatus::InProgress];
            } elseif ($status === ComplaintStatus::Resolved) {
                $progression = [ComplaintStatus::UnderReview, ComplaintStatus::InProgress, ComplaintStatus::Resolved];
            } elseif ($status === ComplaintStatus::Closed) {
                $progression = [ComplaintStatus::UnderReview, ComplaintStatus::InProgress, ComplaintStatus::Resolved, ComplaintStatus::Closed];
            } elseif ($status === ComplaintStatus::Rejected) {
                $progression = [ComplaintStatus::Rejected];
            }

            $prev = ComplaintStatus::Submitted->value;
            $daysOffset = rand(1, 25);
            foreach ($progression as $next) {
                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'actor_id' => ($next === ComplaintStatus::Rejected || $next === ComplaintStatus::UnderReview)
                        ? ($verifyingStaff?->id ?? $citizen->id)
                        : ($assignedStaff?->id ?? $verifyingStaff?->id ?? $citizen->id),
                    'previous_status' => $prev,
                    'new_status' => $next->value,
                    'comment' => $premadeResponses[array_rand($premadeResponses)],
                    'is_internal' => (bool) mt_rand(0, 1),
                    'created_at' => now()->subDays($daysOffset),
                ]);
                $prev = $next->value;
                $daysOffset = max(1, $daysOffset - rand(1, 5));
            }

            // Premade internal note
            if (mt_rand(0, 1)) {
                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'actor_id' => $assignedStaff?->id ?? $verifyingStaff?->id ?? $citizen->id,
                    'previous_status' => $status->value,
                    'new_status' => $status->value,
                    'comment' => $internalNotes[array_rand($internalNotes)],
                    'is_internal' => true,
                    'created_at' => now()->subDays(rand(0, 10)),
                ]);
            }

            try {
                $this->detector->evaluate($complaint);
            } catch (\Throwable $e) {
                // non-fatal
            }

            $created++;
        }

        $this->command->info("Pre-generated complaints completed: {$created}.");
    }
}