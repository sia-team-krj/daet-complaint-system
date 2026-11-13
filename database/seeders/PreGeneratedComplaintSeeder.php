<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * The volume layer of the presentation data: 47 tickets spread across all 25
 * barangays (weighted toward the Centro hotspot), all seven categories, every
 * status, and a deliberate mix of SLA states — some resolved inside the
 * deadline, several overdue, one due soon.
 *
 * Deterministic by seeding the RNG, so re-seeding reproduces the same mix.
 */
class PreGeneratedComplaintSeeder extends Seeder
{
    /** Picked deterministically after mt_srand(). */
    private function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    public function run(): void
    {
        mt_srand(20261113);

        $this->command->info('Seeding volume complaints...');

        $citizens = User::where('role', 'citizen')->orderBy('id')->get();
        if ($citizens->isEmpty()) {
            $this->command->warn('No citizens found; run UserSeeder first.');

            return;
        }

        $staffByDept = User::where('role', 'staff')->get()->groupBy('department_id');
        $admin = User::where('role', 'admin')->first();

        // Status breakdown for the 47: a mature register with a resolved
        // majority for the analytics cards, plus open work in flight.
        $statusPool = array_merge(
            array_fill(0, 14, 'Resolved'),
            array_fill(0, 10, 'Closed'),
            array_fill(0, 9, 'In Progress'),
            array_fill(0, 6, 'Under Review'),
            array_fill(0, 6, 'Submitted'),
            array_fill(0, 2, 'Rejected')
        );
        // Interleave categories so neighbours never share a category.
        $categoryPool = $this->interleave([
            'road_damage' => 9,
            'garbage' => 7,
            'flooding' => 7,
            'sanitation' => 6,
            'plastic_usage' => 6,
            'others' => 7,
            'streetlight' => 5,
        ]);

        // Guarantee all 25 barangays appear at least once: the first 25
        // volume tickets get one barangay each (deterministic shuffle),
        // the rest are drawn from the Centro-weighted list below.
        $shuffledBarangays = User::BARANGAYS;
        for ($i = count($shuffledBarangays) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$shuffledBarangays[$i], $shuffledBarangays[$j]] = [$shuffledBarangays[$j], $shuffledBarangays[$i]];
        }

        $weightedBarangays = array_merge(
            array_fill(0, 6, 'Barangay VI (Centro)'),
            array_fill(0, 3, 'Bagasbas'),
            array_fill(0, 3, 'Barangay I (Hilahod)'),
            array_fill(0, 2, 'Barangay II (Pasig)'),
            array_fill(0, 2, 'Barangay III (Iraya)'),
            array_fill(0, 2, 'Barangay IV (Mantagbac)'),
            array_fill(0, 2, 'Barangay V (Pandan)'),
            array_fill(0, 2, 'Barangay VII (Diego Liñan)'),
            array_fill(0, 2, 'Barangay VIII (Salcedo)'),
            array_diff(User::BARANGAYS, [
                'Barangay VI (Centro)', 'Bagasbas', 'Barangay I (Hilahod)', 'Barangay II (Pasig)',
                'Barangay III (Iraya)', 'Barangay IV (Mantagbac)', 'Barangay V (Pandan)',
                'Barangay VII (Diego Liñan)', 'Barangay VIII (Salcedo)',
            ])
        );

        $content = $this->contentPools();
        $premadeResponses = [
            'Initial review completed by the department.',
            'Field inspection scheduled for this week.',
            'Work assigned to the maintenance crew.',
            'Site cleared; resampling next week.',
            'Remediation under way; barangay informed.',
            'Work completed; awaiting resident confirmation.',
            'Site verified clear after the last rain.',
        ];
        $internalNotes = [
            'Verified on site; photos on the office file.',
            'Resident provided additional evidence.',
            'Coordinated with the assigned barangay.',
            'Marked priority due to safety risk.',
            'No further action required at this time.',
        ];

        $created = 0;

        // In Progress gets a scripted date split so the SLA board shows a
        // healthy mix: 5 overdue, 3 on track, 1 due soon.
        $inProgressPlan = ['overdue', 'overdue', 'overdue', 'overdue', 'overdue', 'on_track', 'on_track', 'on_track', 'due_soon'];

        foreach ($statusPool as $i => $statusValue) {
            $category = $categoryPool[$i % count($categoryPool)];
            $citizen = $citizens[$i % $citizens->count()];
            $barangay = $i < 25
                ? $shuffledBarangays[$i]
                : $weightedBarangays[mt_rand(0, count($weightedBarangays) - 1)];
            [$lat, $lng] = DemoData::barangayPoint($barangay);

            $deptCode = match (true) {
                in_array($category, ['road_damage', 'flooding', 'streetlight'], true) => 'ENG',
                in_array($category, ['garbage', 'sanitation', 'plastic_usage'], true) => 'MENRO',
                default => 'OM',
            };
            $department = Department::where('code', $deptCode)->firstOrFail();
            $deptStaff = $staffByDept[$department->id] ?? collect();
            $assigned = $deptStaff->isNotEmpty() ? $deptStaff->random() : null;
            $verifier = $deptCode === 'ENG'
                ? User::where('email', 'verifier@daetlistens.gov.ph')->first()
                : $admin;

            // Scripted timeline by status.
            $filedDaysAgo = match ($statusValue) {
                'Resolved', 'Closed' => mt_rand(20, 75),
                'In Progress' => $inProgressPlan[$this->takeInProgressSlot()] === 'due_soon' ? mt_rand(12, 14) : mt_rand(15, 70),
                'Under Review' => mt_rand(2, 10),
                default => mt_rand(0, 4),
            };
            // In Progress date split: make the due-soon one routine and verified exactly 13 days ago.
            $filedAt = now()->subDays($filedDaysAgo);
            $priority = $this->pick(mt_rand(0, 10) > 7 ? ['elevated'] : ['routine', 'routine', 'routine', 'elevated', 'urgent']);
            if ($statusValue === 'Submitted' || $statusValue === 'Rejected') {
                $priority = 'routine';
            }

            $verifiedAt = in_array($statusValue, ['Resolved', 'Closed', 'In Progress'], true)
                ? $filedAt->copy()->addDays(mt_rand(1, 3))
                : null;
            $resolvedAt = in_array($statusValue, ['Resolved', 'Closed'], true)
                ? $verifiedAt->copy()->addDays(mt_rand(1, 8))
                : null;
            $closedAt = $statusValue === 'Closed'
                ? $resolvedAt->copy()->addDays(mt_rand(2, 9))
                : null;

            if ($statusValue === 'In Progress') {
                $plan = $inProgressPlan[$this->inProgressSlot - 1];
                if ($plan === 'overdue') {
                    $verifiedAt = now()->subDays(mt_rand(16, 60));
                    $filedAt = $verifiedAt->copy()->subDays(mt_rand(1, 3));
                    $priority = in_array($priority, ['critical', 'urgent'], true) ? 'elevated' : $priority;
                } elseif ($plan === 'on_track') {
                    $verifiedAt = now()->subDays(mt_rand(3, 9));
                    $filedAt = $verifiedAt->copy()->subDays(mt_rand(1, 3));
                } else { // due_soon
                    $priority = 'routine';
                    $verifiedAt = now()->subDays(13);
                    $filedAt = $verifiedAt->copy()->subDays(mt_rand(1, 2));
                }
            }

            $reviewStatus = match ($statusValue) {
                'Submitted' => 'pending',
                'Rejected' => 'rejected',
                'Under Review' => $this->pick(['pending', 'pending', 'pending', 'needs_information', 'needs_information', 'escalated']),
                default => 'verified',
            };

            $isGated = \App\Services\ComplaintPrivacyService::isGated($category);
            $isPublic = $isGated
                ? false
                : (in_array($statusValue, ['Resolved', 'Closed', 'In Progress'], true)
                    ? mt_rand(0, 10) < 6
                    : ($statusValue === 'Submitted' ? mt_rand(0, 10) < 2 : false));

            [$title, $description] = $this->pickContent($content, $category);
            $description .= " Reported near {$barangay}, Daet, Camarines Norte.";

            $urgency = $this->pick(['Low', 'Medium', 'Medium', 'Medium', 'High']);

            $complaint = DemoData::complaint([
                'user' => $citizen,
                'department_code' => $deptCode,
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'barangay' => $barangay,
                'latitude' => $lat,
                'longitude' => $lng,
                'urgency' => $urgency,
                'status' => $statusValue,
                'is_public' => $isPublic,
                'suggested_priority' => $priority,
                'confirmed_priority' => $reviewStatus === 'verified' ? $priority : null,
                'review_status' => $reviewStatus,
                'reviewed_by' => $reviewStatus === 'verified' ? $verifier?->id : ($reviewStatus === 'rejected' ? $admin?->id : null),
                'reviewed_at' => in_array($reviewStatus, ['verified', 'rejected'], true) ? $verifiedAt ?? now()->subDays(mt_rand(0, 5)) : null,
                'verified_at' => $verifiedAt,
                'verifying_staff_id' => $reviewStatus === 'verified' ? $verifier?->id : null,
                'verification_method' => $reviewStatus === 'verified' ? ($deptCode === 'ENG' && mt_rand(0, 1) ? VerificationMethod::SiteVisit->value : VerificationMethod::Desk->value) : null,
                'assigned_staff_id' => $reviewStatus === 'verified' && $statusValue !== 'Submitted' ? $assigned?->id : null,
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
                'closed_by' => $statusValue === 'Closed' ? $this->pick(['resident', 'staff', 'system', 'resident']) : null,
                'spam_status' => 'clear',
                'filed_at' => $filedAt,
            ]);

            // Filing log
            DemoData::log($complaint, $citizen, null, ComplaintStatus::Submitted->value,
                'Complaint filed and routed to '.$department->name.'.', $filedAt);

            // Progression logs
            $progression = match ($statusValue) {
                'Under Review' => [ComplaintStatus::UnderReview],
                'In Progress' => [ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
                'Resolved' => [ComplaintStatus::UnderReview, ComplaintStatus::InProgress, ComplaintStatus::Resolved],
                'Closed' => [ComplaintStatus::UnderReview, ComplaintStatus::InProgress, ComplaintStatus::Resolved, ComplaintStatus::Closed],
                'Rejected' => [ComplaintStatus::Rejected],
                default => [],
            };

            $prev = ComplaintStatus::Submitted->value;
            $at = $filedAt->copy();
            foreach ($progression as $next) {
                $at = $at->copy()->addDays(mt_rand(0, 3));
                $actor = $next === ComplaintStatus::Rejected
                    ? $admin
                    : ($next === ComplaintStatus::UnderReview ? $verifier : $assigned);
                $comment = $next === ComplaintStatus::Rejected
                    ? 'Reviewed and declined: not a matter this office can act on.'
                    : $this->pick($premadeResponses);
                DemoData::log($complaint, $actor, $prev, $next->value, $comment, $at);
                $prev = $next->value;
            }

            // One open information request for the two needs_information tickets,
            // so the information-request screens have a second real entry.
            if ($reviewStatus === 'needs_information') {
                $request = ComplaintInformationRequest::create([
                    'complaint_id' => $complaint->id,
                    'requested_by' => $deptCode === 'ENG' ? $verifier?->id : $admin?->id,
                    'question' => $this->pick([
                        'How long has this been going on, and has the barangay been notified?',
                        'Can you describe the exact location and the nearest landmark?',
                        'Has this been reported before, and if so, when?',
                    ]),
                    'status' => 'open',
                    'round' => 1,
                ]);
                $request->forceFill(['created_at' => $filedAt->copy()->addDays(1), 'updated_at' => $filedAt->copy()->addDays(1)])->save();
            }

            // Occasional internal note so the staff/admin views have texture.
            if (mt_rand(0, 2) === 0 && $statusValue !== 'Submitted') {
                DemoData::log($complaint, $assigned ?? $verifier ?? $admin, $prev, $prev,
                    $this->pick($internalNotes), $at->copy()->addDays(mt_rand(0, 2)), true);
            }

            // Audit trail
            DemoData::activity($citizen, $department, 'complaint.filed', $complaint,
                "Complaint {$complaint->ticket_id} was filed and routed to {$department->name}.", [], $filedAt);
            if ($reviewStatus === 'verified') {
                DemoData::activity($verifier, $department, 'complaint.reviewed', $complaint,
                    "{$complaint->ticket_id} was verified.", ['review_status' => 'verified'], $verifiedAt);
            }
            if (in_array($statusValue, ['In Progress', 'Resolved', 'Closed'], true)) {
                DemoData::activity($assigned ?? $verifier, $department, 'complaint.status_updated', $complaint,
                    "{$complaint->ticket_id} moved to In Progress.", ['from' => 'Under Review', 'to' => 'In Progress'], $verifiedAt?->copy()->addDay() ?? now());
            }
            if (in_array($statusValue, ['Resolved', 'Closed'], true)) {
                DemoData::activity($assigned ?? $verifier, $department, 'complaint.status_updated', $complaint,
                    "{$complaint->ticket_id} moved to Resolved.", ['from' => 'In Progress', 'to' => 'Resolved'], $resolvedAt);
            }

            $created++;
        }

        $this->command->info("Volume complaints completed: {$created}. Total presentation tickets: ".($created + 14).'.');
    }

    /** @var int */
    private int $inProgressSlot = 0;

    private function takeInProgressSlot(): int
    {
        return $this->inProgressSlot++;
    }

    /**
     * Round-robin the categories so a seeded order never runs long stretches
     * of the same category.
     *
     * @param array<string, int> $counts
     * @return list<string>
     */
    private function interleave(array $counts): array
    {
        $pool = [];
        $remaining = $counts;
        $order = array_keys($counts);
        $index = 0;

        while (array_sum($remaining) > 0) {
            $key = $order[$index % count($order)];
            if (($remaining[$key] ?? 0) > 0) {
                $pool[] = $key;
                $remaining[$key]--;
            }
            $index++;
            if ($index > 10000) {
                break;
            }
        }

        return $pool;
    }

    /**
     * 6–8 unique (title, description) pairs per category; language mix kept
     * mostly English with a few Filipino entries.
     */
    private function contentPools(): array
    {
        return [
            'road_damage' => [
                ['Large pothole on the national road', 'A large pothole has opened on the national road and is forcing motorists into the oncoming lane. Motorcycle riders are at risk, especially at night.'],
                ['Cracked pavement near the school', 'The pavement directly in front of the elementary school is cracked and heaving. Small children trip on it on their way to class every morning.'],
                ['Road cave-in after heavy rain', 'A section of the road caved in after the last heavy rain. Water collects there and the surface is collapsing further each week.'],
                ['Uneven road surface by the market', 'The road surface approaching the public market is uneven and broken. Vendors and tricycles swerve to avoid it all day.'],
                ['Sirang labasan ng drainage; butas sa kalsada', 'Ilang bahagi ng kalsada ang gumuho matapos ang ulan. Butas-butas na ito at delikado sa mga motor ngayong gabi.'],
                ['Persistent potholes on the barangay road', 'The concrete pothole repairs on this barangay road keep breaking open after every rain. The patches are barely level with the road surface.'],
                ['Deep pothole near the waiting shed', 'A deep pothole beside the tricycle waiting shed swallows a whole wheel if a rider hits it at speed. It needs proper compaction, not another quick patch.'],
                ['Edge of the asphalted road is breaking away', 'The shoulder of the asphalted road is breaking away in chunks. Motorcycles drifting to the edge hit loose pieces and lose control.'],
                ['Open manhole cover on the national highway', 'A manhole cover near the highway bend is broken and sitting askew. It is a death trap at night for inbound motorcycles.'],
            ],
            'flooding' => [
                ['Flooded street after downpour', 'The street floods ankle-deep for hours after every downpour. Residents cannot reach the main road and children are kept home from school.'],
                ['Drainage clogged near the creek', 'The drainage outlet into the creek is choked with trash. Water backs up into the lane every time it rains and garbage washes onto the road.'],
                ['Standing water on pedestrian lane', 'Standing water sits on the pedestrian lane for days after rain. It breeds mosquitoes and the lane is impassable for elders and wheelchairs.'],
                ['Flooded underpass after storm', 'The low underpass section floods knee-deep after every storm; tricycles stall there and drivers have had to be pulled out twice.'],
                ['Barangay creek overflows onto the road', 'The creek behind the basketball court overflows onto the feeder road during heavy rain. The road is impassable for hours.'],
                ['Binaha ang side street tuwing tag-ulan', 'Tuwing malakas ang ulan ay umaabot sa tuhod ang tubig sa side street. Humihina ang drainage at bumabara ito ng basura.'],
                ['Roadside drainage channeled into the residences', 'The roadside drain has been redirected and now pours straight into two residences on every heavy rain. Their floors are soaked nightly.'],
            ],
            'streetlight' => [
                ['Streetlight out on main road', 'The streetlight on the main road has been out for two weeks; the corner is pitch black and accidents are narrowly avoided nightly.'],
                ['Broken lamp post near the plaza', 'The lamp post near the public plaza is leaning and its bulb is dead. Children cross there on the way home from church.'],
                ['Dark corner by the barangay hall', 'The corner behind the barangay hall has been dark for a week, making the alley the obvious route for people carrying cash at night.'],
                ['Flickering light near the school', 'The light outside the school fence flickers on and off. It fails entirely just as classes let out in the late afternoon.'],
                ['Walang ilaw sa unahan ng basketball court', 'Ilang araw nang walang ilaw sa unahan ng basketball court tuwing gabi. Mapanganib sa mga naglalaro at umuwi.'],
            ],
            'garbage' => [
                ['Uncollected garbage by the health center', 'Garbage has not been collected at the health center for a week; the bins overflow onto the sidewalk and the smell reaches the clinic.'],
                ['Overflowing bin near the market', 'The communal bin near the public market overflows every day. Pest control is impossible and buyers complain about the smell.'],
                ['Illegal dumping near the creek', 'Someone dumps household waste behind the market row near the creek. It is collected by stray dogs and washed into the creek on high water.'],
                ['Missed collection on our street', 'Our street was skipped on yesterday\'s collection. The truck went to the next barangay but never came back for us.'],
                ['Basura hindi kinokolekta sa tabi ng daycare', 'Tatlong araw nang hindi nakukuha ang basura sa tabi ng daycare. Amoy na amoy ang buong lansangan.'],
                ['Garbage truck blocked by parked vehicles', 'Parked vehicles block the collection truck at the cul-de-sac, so the whole row is skipped every week though the driver is willing.'],
                ['Bulk waste abandoned on the roadside', 'A sofa and construction debris were abandoned on the roadside last Sunday. They are now a road hazard and an eyesore.'],
            ],
            'sanitation' => [
                ['Clogged drainage near the market', 'Drainage near the market is clogged solid with grease and food waste. Greywater floods the dry-goods lane during washing hours.'],
                ['Foul odor from sewer line', 'The sewer line beside our row of houses vents a foul smell at night. It is worst on humid evenings and no one has come to check the vent.'],
                ['Blocked canal causing stagnant water', 'The canal along the barangay boundary is blocked with silt and branches. Water stands motionless for days and mosquitoes swarm.'],
                ['Dirty canal beside the road', 'The open canal beside the road is full of black, stagnant water. Children walk past it to school every morning.'],
                ['Baradong canal sa tapat ng pampublikong ulan', 'Baradong-barado ang canal sa tapat ng edsa drainage; tuwing umuulan ay umuumbok ang putik hanggang sa bahay.'],
                ['Standing water behind the market row', 'Water stands behind the market row permanently. It is black and carries kitchen grease from the eateries.'],
            ],
            'plastic_usage' => [
                ['Store using plastic bags for every purchase', 'A convenience store packs every purchase in single-use plastic bags, including items meant for immediate consumption, contrary to the municipal ordinance.'],
                ['Plastic packaging instead of paper', 'A food stall wraps every order in plastic film and Styrofoam instead of the paper or leaves agreed with the association. The pile of plastic grows daily.'],
                ['Food stall serving in plastic containers', 'A food stall serves every meal in single-use plastic containers. The discarded containers choke the sidewalk drain within meters.'],
                ['Shop giving out plastic sachets', 'A retail shop keeps selling detergent and shampoo in plastic sachets long after the association"s commitment to refill stations.'],
                ['Plastic wrap-around every sari-sari shelf', 'A sari-sari store over-wraps products in multiple layers of plastic, doubling the plastic waste from a single row of shelves.'],
                ['Plastic cups at the barangay water refilling station', 'The refilling station hands out single-use plastic cups with every refill instead of asking customers to bring their own.'],
            ],
            'others' => [
                ['Stray dogs near the elementary school', 'A pack of stray dogs has settled near the elementary school gate. They growl at children every afternoon dismissal.'],
                ['Request for public assistance', 'A senior citizen household needs assistance clearing storm debris from the roof before the next heavy rain.'],
                ['Community safety concern', 'The public plaza lighting was vandalized two weekends ago and the entire north side is dark. Residents avoid the plaza after dinner.'],
                ['Water supply interrupted for days', 'Our barangay has had no water supply for four days. The private utility truck has not passed our street since the maintenance week.'],
                ['Noise from the cockpit past midnight', 'The cockpit"s PA system is audible two barangays away past midnight on weekends. Sleep is impossible for the row of houses facing it.'],
                ['Kahilingan para sa tubig sa aming kanto', 'Apat na araw nang walang tubig ang kantong aming kinatatayuan. Sana po kami ay mapuntahan ng tubig.'],
                ['Missed garbage schedule last week', 'The garbage schedule posted at the waiting shed was skipped twice last week and no notice was posted.'],
            ],
        ];
    }

    /**
     * @param array<string, list<array{0: string, 1: string}>> $content
     * @return array{0: string, 1: string}
     */
    private function pickContent(array $content, string $category): array
    {
        $pool = $content[$category];
        $entry = $pool[mt_rand(0, count($pool) - 1)];

        return [$entry[0], $entry[1]];
    }
}
