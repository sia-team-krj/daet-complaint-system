<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\FeedbackStatus;
use App\Enums\HoldReason;
use App\Enums\InformationRequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintFeedback;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * Curated, story-driven complaints for the capstone demo.
 *
 * Focus: per-complaint feedback (new), lifecycle/queues, resident actions and
 * transparency, and special cases (hold, duplicate, spam/review flag,
 * escalated, overdue, open info request). Kept small (~16 tickets) so each
 * scenario is easy to find and narrate.
 */
class DemoComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding curated demo complaints...');

        $admin = User::where('role', 'admin')->firstOrFail();
        $verifier = User::where('email', 'verifier.eng@daetlistens.gov.ph')->firstOrFail();
        $menroVerifier = User::where('email', 'verifier.menro@daetlistens.gov.ph')->firstOrFail();
        $engStaff = User::where('email', 'staff.eng.1@daetlistens.gov.ph')->firstOrFail();
        $mayor = User::where('email', 'staff.om.1@daetlistens.gov.ph')->firstOrFail();
        $menroStaff = User::where('email', 'staff.menro.1@daetlistens.gov.ph')->firstOrFail();

        $pedro = User::where('email', 'aling@gmail.com')->firstOrFail();
        $nena = User::where('email', 'bela@gmail.com')->firstOrFail();
        $jose = User::where('email', 'carlo@gmail.com')->firstOrFail();
        $maria = User::where('email', 'dana@gmail.com')->firstOrFail();
        $rodel = User::where('email', 'eduardo@gmail.com')->firstOrFail();
        $carmen = User::where('email', 'eduardo@gmail.com')->firstOrFail();

        $eng = Department::where('code', 'ENG')->firstOrFail();
        $menro = Department::where('code', 'MENRO')->firstOrFail();
        $om = Department::where('code', 'OM')->firstOrFail();

        // ── 1. Submitted / pending review (new in Engineering queue) ──────
        [$lat, $lng] = DemoData::barangayPoint('Barangay I');
        $c1 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Pothole forming near the Barangay I crossing',
            'description' => 'May lumalaking butas sa kalsada malapit sa tawiran sa Barangay I. Delikado po sa mga motorsiklo lalo na kapag gabi.',
            'filed_at' => now()->subHours(10),
            'barangay' => 'Barangay I',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'High',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'review_status' => 'pending',
        ]);
        DemoData::log($c1, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $c1->created_at);
        DemoData::activity($pedro, $eng, 'complaint.submitted', $c1, "Submitted {$c1->ticket_id}: {$c1->title}.", [], $c1->created_at);

        // ── 2. In progress, assigned (Engineering field work) ─────────────
        [$lat, $lng] = DemoData::barangayPoint('Bagasbas');
        $filed = now()->subDays(6)->setTime(9, 20);
        $reviewed = now()->subDays(5)->setTime(10, 0);
        $started = now()->subDays(4)->setTime(8, 30);
        $c2 = DemoData::complaint([
            'user' => $jose,
            'department_code' => 'ENG',
            'category' => 'streetlight',
            'title' => 'Streetlight flickering along Bagasbas road',
            'description' => 'Patay-sindi ang ilaw ng poste sa kahabaan ng kalsada sa Bagasbas tuwing gabi. Mahirap po ang daan para sa mga dumadaan.',
            'filed_at' => $filed,
            'barangay' => 'Bagasbas',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $engStaff->id,
        ]);
        DemoData::log($c2, $jose, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c2, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified the report details and location.', $reviewed, true);
        DemoData::log($c2, $engStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Inspection scheduled; replacement parts requested.', $started);
        DemoData::activity($engStaff, $eng, 'complaint.status_changed', $c2, "Work started on {$c2->ticket_id}.", ['status' => ComplaintStatus::InProgress->value], $started);

        // ── 3. Needs information — OPEN request (Pedro can reply) ──────────
        [$lat, $lng] = DemoData::barangayPoint('Camambugan');
        $filed = now()->subDays(5)->setTime(7, 45);
        $reviewed = now()->subDays(4)->setTime(11, 0);
        $asked = now()->subDays(3)->setTime(9, 10);
        $c3 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'MENRO',
            'category' => 'garbage_collection',
            'title' => 'Missed garbage pickup near Camambugan health center',
            'description' => 'Hindi po nahakot ang basura malapit sa health center sa Camambugan noong nakaraang schedule. Umaamoy na po.',
            'filed_at' => $filed,
            'barangay' => 'Camambugan',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::UnderReview->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'needs_information',
            'reviewed_by' => $menroVerifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $menroVerifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'review_notes' => 'Please share the exact pickup point or a recent photo.',
        ]);
        DemoData::log($c3, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c3, $menroVerifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Asked the resident for more specific location details.', $reviewed, true);
        $info = ComplaintInformationRequest::create([
            'complaint_id' => $c3->id,
            'requested_by' => $menroVerifier->id,
            'question' => 'Maaari po bang magpadala ng mas malinaw na larawan o tukuyin ang eksaktong pickup point malapit sa health center?',
            'status' => InformationRequestStatus::Open->value,
            'round' => 1,
        ]);
        $info->forceFill(['created_at' => $asked, 'updated_at' => $asked])->save();

        // ── 4. On hold (awaiting materials) ───────────────────────────────
        [$lat, $lng] = DemoData::barangayPoint('Lag-on');
        $filed = now()->subDays(20)->setTime(8, 0);
        $reviewed = now()->subDays(18)->setTime(9, 30);
        $started = now()->subDays(16)->setTime(13, 0);
        $held = now()->subDays(7)->setTime(10, 0);
        $c4 = DemoData::complaint([
            'user' => $maria,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Uneven pavement on the Lag-on feeder road',
            'description' => 'May lubak at hindi pantay na bahagi ng kalsada sa Lag-on feeder road. Nahihirapan ang mga tricycle dumaan.',
            'filed_at' => $filed,
            'barangay' => 'Lag-on',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $engStaff->id,
            'hold_reason' => HoldReason::AwaitingMaterials->value,
            'hold_note' => 'Waiting for asphalt materials delivery.',
            'hold_internal_note' => 'Procurement request submitted; delivery expected next week.',
            'hold_expected_resume_at' => now()->addDays(5)->toDateString(),
            'hold_started_at' => $held,
            'hold_started_by' => $engStaff->id,
            'hold_total_minutes' => 60 * 24 * 7,
        ]);
        DemoData::log($c4, $maria, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c4, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified and prioritized for repair.', $reviewed, true);
        DemoData::log($c4, $engStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Site inspected; repair scheduled.', $started);
        DemoData::log($c4, $engStaff, ComplaintStatus::InProgress->value, ComplaintStatus::InProgress->value, 'Work paused: awaiting asphalt materials.', $held, true);
        DemoData::activity($engStaff, $eng, 'complaint.put_on_hold', $c4, "Placed {$c4->ticket_id} on hold (awaiting materials).", ['hold_reason' => HoldReason::AwaitingMaterials->value], $held);

        // ── 5. Escalated (Mayor’s Office / coordination needed) ───────────
        [$lat, $lng] = DemoData::barangayPoint('Barangay V');
        $filed = now()->subDays(9)->setTime(14, 0);
        $reviewed = now()->subDays(8)->setTime(10, 15);
        $c5 = DemoData::complaint([
            'user' => $carmen,
            'department_code' => 'OM',
            'category' => 'water_service',
            'title' => 'Intermittent water supply affecting Barangay V households',
            'description' => 'Putol-putol po ang suplay ng tubig sa Barangay V nitong nakaraang linggo. Maraming sambahayan ang apektado.',
            'filed_at' => $filed,
            'barangay' => 'Barangay V',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'High',
            'status' => ComplaintStatus::UnderReview->value,
            'is_public' => true,
            'suggested_priority' => 'urgent',
            'confirmed_priority' => 'urgent',
            'review_status' => 'escalated',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::Desk->value,
            'assigned_staff_id' => $mayor->id,
            'review_notes' => 'Escalated for coordination with the water service provider and concerned offices.',
        ]);
        DemoData::log($c5, $carmen, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c5, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Escalated: requires coordination beyond a single office.', $reviewed, true);
        DemoData::activity($mayor, $om, 'complaint.reviewed', $c5, "Escalated {$c5->ticket_id} for inter-office coordination.", ['review_status' => 'escalated'], $reviewed);

        // ── 6. Overdue (verified, in progress, past SLA) ──────────────────
        [$lat, $lng] = DemoData::barangayPoint('Barangay II');
        $filed = now()->subDays(25)->setTime(8, 30);
        $reviewed = now()->subDays(23)->setTime(9, 0);
        $started = now()->subDays(22)->setTime(10, 0);
        $c6 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'ENG',
            'category' => 'flooding',
            'title' => 'Drainage still clogged along Barangay II side street',
            'description' => 'Barado pa rin ang drainage sa gilid ng kalsada sa Barangay II kaya mabilis bumaha kahit maikling ulan.',
            'filed_at' => $filed,
            'barangay' => 'Barangay II',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'High',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $engStaff->id,
        ]);
        DemoData::log($c6, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c6, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified; clearing crew to be scheduled.', $reviewed, true);
        DemoData::log($c6, $engStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Initial clearing done; follow-up needed.', $started);

        // ── 7. Resolved (Pedro) — FEEDBACK STATE A: can submit live ───────
        [$lat, $lng] = DemoData::barangayPoint('Barangay III');
        $filed = now()->subDays(14)->setTime(9, 0);
        $reviewed = now()->subDays(13)->setTime(11, 0);
        $started = now()->subDays(11)->setTime(8, 0);
        $resolved = now()->subDays(2)->setTime(15, 30);
        $c7 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Loose pavement slab near Barangay III waiting shed',
            'description' => 'May umangat na bahagi ng semento malapit sa waiting shed sa Barangay III. Maaaring madapa ang mga dumadaan.',
            'filed_at' => $filed,
            'barangay' => 'Barangay III',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $engStaff->id,
            'resolved_at' => $resolved,
        ]);
        DemoData::log($c7, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c7, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on-site.', $reviewed, true);
        DemoData::log($c7, $engStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Repair crew dispatched.', $started);
        DemoData::log($c7, $engStaff, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Pavement slab repaired and secured. Resident may now confirm and rate the service.', $resolved);

        // ── 8. Closed + published feedback on transparency (STATE B) ──────
        [$lat, $lng] = DemoData::barangayPoint('Barangay IV');
        $filed = now()->subDays(30)->setTime(10, 0);
        $reviewed = now()->subDays(28)->setTime(9, 0);
        $started = now()->subDays(26)->setTime(8, 0);
        $resolved = now()->subDays(20)->setTime(14, 0);
        $closed = now()->subDays(18)->setTime(11, 0);
        $c8 = DemoData::complaint([
            'user' => $nena,
            'department_code' => 'MENRO',
            'category' => 'garbage_collection',
            'title' => 'Uncollected waste pile near Barangay IV market area',
            'description' => 'Naiwan ang tambak ng basura malapit sa palengke sa Barangay IV. Naayos na po matapos maiparating.',
            'filed_at' => $filed,
            'barangay' => 'Barangay IV',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Closed->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $menroVerifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $menroVerifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $menroStaff->id,
            'resolved_at' => $resolved,
            'closed_at' => $closed,
            'closed_by' => 'resident',
        ]);
        DemoData::log($c8, $nena, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c8, $menroVerifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified and forwarded for collection.', $reviewed, true);
        DemoData::log($c8, $menroStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Collection crew scheduled.', $started);
        DemoData::log($c8, $menroStaff, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Waste collected and area cleaned.', $resolved);
        DemoData::log($c8, $nena, ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value, 'Resident confirmed the resolution.', $closed);
        ComplaintFeedback::create([
            'complaint_id' => $c8->id,
            'user_id' => $nena->id,
            'rating' => 5,
            'comment' => 'Mabilis ang aksyon ng MENRO. Salamat po sa maayos na pagkuha ng basura.',
            'status' => FeedbackStatus::Published->value,
            'published_at' => $closed->copy()->addHours(3),
            'reviewed_by' => $admin->id,
            'reviewed_at' => $closed->copy()->addHours(3),
        ]);

        // ── 9. Resolved + pending feedback comment (STATE C, moderation) ──
        [$lat, $lng] = DemoData::barangayPoint('San Isidro');
        $filed = now()->subDays(12)->setTime(8, 15);
        $reviewed = now()->subDays(11)->setTime(9, 30);
        $started = now()->subDays(9)->setTime(10, 0);
        $resolved = now()->subDays(1)->setTime(16, 0);
        $c9 = DemoData::complaint([
            'user' => $rodel,
            'department_code' => 'ENG',
            'category' => 'streetlight',
            'title' => 'Streetlight out near San Isidro chapel',
            'description' => 'Walang ilaw ang poste malapit sa chapel sa San Isidro. Madilim po tuwing gabi.',
            'filed_at' => $filed,
            'barangay' => 'San Isidro',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $engStaff->id,
            'resolved_at' => $resolved,
        ]);
        DemoData::log($c9, $rodel, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c9, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified.', $reviewed, true);
        DemoData::log($c9, $engStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Bulb replacement scheduled.', $started);
        DemoData::log($c9, $engStaff, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Streetlight repaired and tested.', $resolved);
        ComplaintFeedback::create([
            'complaint_id' => $c9->id,
            'user_id' => $rodel->id,
            'rating' => 4,
            'comment' => 'Naayos naman po agad ang ilaw, sana lang po mas mabilis ang update habang inaayos.',
            'status' => FeedbackStatus::Pending->value,
            'published_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        // ── 10. Original complaint (for duplicate pair) ───────────────────
        [$lat, $lng] = DemoData::barangayPoint('Boroboro');
        $filed = now()->subDays(10)->setTime(7, 30);
        $reviewed = now()->subDays(9)->setTime(10, 0);
        $started = now()->subDays(8)->setTime(9, 0);
        $c10 = DemoData::complaint([
            'user' => $maria,
            'department_code' => 'MENRO',
            'category' => 'garbage_collection',
            'title' => 'Garbage not collected along Boroboro main road',
            'description' => 'Hindi po nakolekta ang basura sa kahabaan ng pangunahing kalsada sa Boroboro mula pa noong nakaraang schedule.',
            'filed_at' => $filed,
            'barangay' => 'Boroboro',
            'address_text' => 'Main road, Barangay Boroboro, Daet, Camarines Norte',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $menroVerifier->id,
            'reviewed_at' => $reviewed,
            'verified_at' => $reviewed,
            'verifying_staff_id' => $menroVerifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $menroStaff->id,
        ]);
        DemoData::log($c10, $maria, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c10, $menroVerifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified and assigned to collection team.', $reviewed, true);
        DemoData::log($c10, $menroStaff, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Collection team dispatched.', $started);

        // ── 11. Flagged duplicate pair (compare with original) ────────────
        [$lat, $lng] = DemoData::barangayPoint('Boroboro');
        $filed = now()->subDays(9)->setTime(18, 10);
        $c11 = DemoData::complaint([
            'user' => $jose,
            'department_code' => 'MENRO',
            'category' => 'garbage_collection',
            'title' => 'Uncollected trash on Boroboro main road since last pickup',
            'description' => 'Hindi pa rin po nahahakot ang mga basura sa main road ng Boroboro simula noong huling schedule ng koleksyon.',
            'filed_at' => $filed,
            'barangay' => 'Boroboro',
            'address_text' => 'Main road, Barangay Boroboro, Daet, Camarines Norte',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'review_status' => 'pending',
            'spam_status' => 'review',
            'spam_score' => 35,
            'spam_reasons' => ['Possible duplicate of a similar recent report in the same category.'],
            'duplicate_of_id' => $c10->id,
            'similarity_score' => 0.86,
        ]);
        DemoData::log($c11, $jose, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c11, null, ComplaintStatus::Submitted->value, ComplaintStatus::Submitted->value, "Flagged for review: possible duplicate of {$c10->ticket_id} (86% similar).", $filed->copy()->addMinutes(1), true);
        DemoData::activity($menroStaff, $menro, 'complaint.moderation_flagged', $c11, "{$c11->ticket_id} flagged as a possible duplicate of {$c10->ticket_id}.", ['duplicate_of_id' => $c10->id, 'similarity_score' => 0.86], $filed->copy()->addMinutes(1));

        // ── 12. Spam/review-flagged complaint (admin can clear flag) ──────
        [$lat, $lng] = DemoData::barangayPoint('Barangay VI');
        $filed = now()->subDays(2)->setTime(22, 40);
        $c12 = DemoData::complaint([
            'user' => $carmen,
            'department_code' => 'OM',
            'category' => 'other_concern',
            'title' => 'FREE MONEY click here visit now for prize',
            'description' => 'Visit now click here for free money and limited offer. Guaranteed prize now.',
            'filed_at' => $filed,
            'barangay' => 'Barangay VI',
            'latitude' => $lat, 'longitude' => $lng,
            'urgency' => 'Low',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'review_status' => 'pending',
            'spam_status' => 'review',
            'spam_score' => 70,
            'spam_reasons' => ['Contains a link', 'Contains promotional or spam language'],
        ]);
        DemoData::log($c12, $carmen, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
        DemoData::log($c12, null, ComplaintStatus::Submitted->value, ComplaintStatus::Submitted->value, 'Flagged for moderation review due to spam-like content.', $filed->copy()->addMinute(), true);

        // ── 13-16. Same purpose, different wording (not auto-linked) ──────
        // Garbage/sanitation concern in the same barangay/category; varied
        // wording/descriptions so lexical similarity stays below auto-linking,
        // demonstrating the limitation and queue/filter workflow.
        $similarCases = [
            [
                $pedro, 'Alawihao',
                'Basura piling up beside Alawihao waiting shed',
                'Napansin ko po na ilang araw nang may nakatambak na basura sa tabi ng waiting shed sa Alawihao. Sana po maaksiyunan.',
                8,
            ],
            [
                $nena, 'Alawihao',
                'Amoy mula sa hindi nahakot na kalat sa kanto ng Alawihao',
                'Medyo matindi na po ang amoy sa may kanto dahil may mga basurang hindi pa nakukuha. Baka po puwedeng masilip ng koleksyon.',
                7,
            ],
            [
                $jose, 'Alawihao',
                'Trash left near the Alawihao roadside store',
                'May mga sako at plastic na naiwan malapit sa tindahan sa gilid ng kalsada sa Alawihao. Nakakaabala po sa dumadaan.',
                6,
            ],
            [
                $maria, 'Alawihao',
                'Kalat na resibo at supot sa tabi ng daan sa Alawihao',
                'Tuwing hapon po ay may mga nagiiwan ng supot at kalat sa tabi ng daan sa Alawihao. Sana po matulungan sa paglilinis.',
                5,
            ],
        ];

        foreach ($similarCases as [$resident, $barangay, $title, $description, $daysAgo]) {
            [$lat, $lng] = DemoData::barangayPoint($barangay);
            $filed = now()->subDays($daysAgo)->setTime(8, 15);
            $c = DemoData::complaint([
                'user' => $resident,
                'department_code' => 'MENRO',
                'category' => 'garbage_collection',
                'title' => $title,
                'description' => $description,
                'filed_at' => $filed,
                'barangay' => $barangay,
                'latitude' => $lat, 'longitude' => $lng,
                'urgency' => 'Medium',
                'status' => ComplaintStatus::Submitted->value,
                'is_public' => true,
                'suggested_priority' => 'routine',
                'review_status' => 'pending',
                'spam_status' => 'clear',
                'spam_score' => 0,
                'similarity_score' => 0,
                'duplicate_of_id' => null,
            ]);
            DemoData::log($c, $resident, null, ComplaintStatus::Submitted->value, 'Complaint filed by the resident.', $filed);
            DemoData::activity($resident, $menro, 'complaint.submitted', $c, "Submitted {$c->ticket_id}: {$c->title}.", [], $filed);
        }

        $this->command->info('Curated demo complaints completed (16 tickets).');
    }
}
