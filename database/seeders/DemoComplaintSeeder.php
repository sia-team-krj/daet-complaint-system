<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\HoldReason;
use App\Enums\PublicationStatus;
use App\Enums\VerificationMethod;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * The handcrafted presentation tickets.
 *
 * Every ticket here is deliberately shaped to exercise one capability of the
 * system on demand: the review queue, an open information request, a
 * duplicate, a spam flag, an escalation, a hold, a resolved/closed case with
 * resident feedback, a publication approved by the Mayor's Office, a
 * publication pending approval, and a water-service concern routed to the
 * Mayor's Office. Dates are backdated relative to now so the three-month
 * window on the dashboards and charts has something to draw.
 */
class DemoComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding handcrafted demo complaints...');

        $admin = User::where('role', 'admin')->firstOrFail();
        $verifier = User::where('email', 'verifier@daetlistens.gov.ph')->firstOrFail(); // Engineering, can_verify
        $juan = User::where('email', 'engineering@daetlistens.gov.ph')->firstOrFail();
        $ana = User::where('email', 'mayor@daetlistens.gov.ph')->firstOrFail();
        $carlos = User::where('email', 'menro@daetlistens.gov.ph')->firstOrFail();

        $pedro = User::where('email', 'pedro@gmail.com')->firstOrFail();
        $nena = User::where('email', 'nena@gmail.com')->firstOrFail();
        $jose = User::where('email', 'jose@gmail.com')->firstOrFail();
        $maria = User::where('email', 'maria.santos@gmail.com')->firstOrFail();
        $rodel = User::where('email', 'rodel.villanueva@gmail.com')->firstOrFail();
        $liza = User::where('email', 'liza.ocampo@gmail.com')->firstOrFail();
        $gabriel = User::where('email', 'gabriel.reyes@gmail.com')->firstOrFail();
        $carmen = User::where('email', 'carmen.delarosa@gmail.com')->firstOrFail();

        $eng = Department::where('code', 'ENG')->firstOrFail();
        $om = Department::where('code', 'OM')->firstOrFail();
        $menro = Department::where('code', 'MENRO')->firstOrFail();

        // ── S1 · Filipino road ticket, verified on site, now OVERDUE ──
        $filed = now()->subDays(12);
        $p = DemoData::barangayPoint('Barangay VI (Centro)');
        $s1 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Malaking butas sa national highway malapit sa waiting shed',
            'description' => 'Malaking butas ang gumuho sa tapat ng waiting shed sa national highway. De-motor ang mga motorista kapag rush hour at delikado na ito sa mga taong tumatawid.',
            'barangay' => 'Barangay VI (Centro)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'High',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'urgent',
            'confirmed_priority' => 'urgent',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => now()->subDays(11),
            'verified_at' => now()->subDays(11),
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $juan->id,
            'filed_at' => $filed,
        ]);
        DemoData::log($s1, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to Engineering Office.', $filed);
        DemoData::log($s1, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on site — severe pothole on the inbound lane; recommend urgent repair.', now()->subDays(11));
        DemoData::log($s1, $juan, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Crew assigned; excavation and patch repair scheduled.', now()->subDays(10));
        DemoData::log($s1, $juan, ComplaintStatus::InProgress->value, ComplaintStatus::InProgress->value, 'Asphalt mix delivery confirmed for November 16; crew mobilizes November 17.', now()->subDays(2), true);
        DemoData::activity($pedro, $eng, 'complaint.filed', $s1, "Complaint {$s1->ticket_id} was filed and routed to Engineering Office.", [], $filed);
        DemoData::activity($verifier, $eng, 'complaint.reviewed', $s1, "{$s1->ticket_id} was verified after a site visit.", ['review_status' => 'verified', 'method' => 'site_visit'], now()->subDays(11));
        DemoData::activity($juan, $eng, 'complaint.status_updated', $s1, "{$s1->ticket_id} moved to In Progress.", ['from' => 'Under Review', 'to' => 'In Progress'], now()->subDays(10));
        DemoData::activity($juan, $eng, 'complaint.note_added', $s1, "Internal note added to {$s1->ticket_id}.", [], now()->subDays(2));

        // ── S2 · Flooding, resolved within the SLA (met) ──
        $filed = now()->subDays(35);
        $p = DemoData::barangayPoint('Barangay V (Pandan)');
        $s2 = DemoData::complaint([
            'user' => $nena,
            'department_code' => 'ENG',
            'category' => 'flooding',
            'title' => 'Flooded pedestrian lane after heavy rain in Pandan',
            'description' => 'The pedestrian lane beside Barangay V (Pandan) Elementary School stays flooded for hours after every heavy rain. Vendors and children crossing the road wade through ankle-deep water on the way to school.',
            'barangay' => 'Barangay V (Pandan)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => now()->subDays(34),
            'verified_at' => now()->subDays(34),
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $juan->id,
            'resolved_at' => now()->subDays(29),
            'filed_at' => $filed,
        ]);
        DemoData::log($s2, $nena, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to Engineering Office.', $filed);
        DemoData::log($s2, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on site — blocked catch basin confirmed; clearing scheduled.', now()->subDays(34));
        DemoData::log($s2, $juan, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Crew cleared the catch basin and re-graded the shoulder.', now()->subDays(31));
        DemoData::log($s2, $juan, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Lane drains properly after the last two heavy rains. Resolution verified on site.', now()->subDays(29));
        DemoData::activity($nena, $eng, 'complaint.filed', $s2, "Complaint {$s2->ticket_id} was filed and routed to Engineering Office.", [], $filed);
        DemoData::activity($verifier, $eng, 'complaint.reviewed', $s2, "{$s2->ticket_id} was verified after a site visit.", ['review_status' => 'verified'], now()->subDays(34));
        DemoData::activity($juan, $eng, 'complaint.status_updated', $s2, "{$s2->ticket_id} moved to Resolved.", ['from' => 'In Progress', 'to' => 'Resolved'], now()->subDays(29));

        // ── S3 · Fresh streetlight ticket still waiting on verification ──
        $filed = now()->subHours(20);
        $p = DemoData::barangayPoint('Barangay VIII (Salcedo)');
        $s3 = DemoData::complaint([
            'user' => $jose,
            'department_code' => 'ENG',
            'category' => 'streetlight',
            'title' => 'Streetlight out at the barangay hall waiting area',
            'description' => 'The streetlight directly outside the barangay hall waiting area has been dark for a week. Residents queueing for barangay clearance walk home in total darkness after evening sessions.',
            'barangay' => 'Barangay VIII (Salcedo)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Low',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'pending',
            'filed_at' => $filed,
        ]);
        DemoData::log($s3, $jose, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to Engineering Office.', $filed);
        DemoData::activity($jose, $eng, 'complaint.filed', $s3, "Complaint {$s3->ticket_id} was filed and routed to Engineering Office.", [], $filed);

        // ── S4 · Garbage, Filipino, office asked for details — still OPEN ──
        $filed = now()->subDays(6);
        $p = DemoData::barangayPoint('Barangay VI (Centro)');
        $s4 = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'MENRO',
            'category' => 'garbage',
            'title' => 'Hindi kinokolekta ang basura sa tabi ng health center',
            'description' => 'Tatlong linggo nang hindi nakukuha ang basura malapit sa health center. Ang amoy ay nakakaabala sa mga pasyente at hinihila nito ang mga peste sa canopy ng ospital.',
            'barangay' => 'Barangay VI (Centro)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::UnderReview->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'needs_information',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(5),
            'verifying_staff_id' => $carlos->id,
            'filed_at' => $filed,
        ]);
        DemoData::log($s4, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to MENRO.', $filed);
        DemoData::log($s4, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'The office needs more detail before scheduling collection. Awaiting the resident.', now()->subDays(5));
        $req = ComplaintInformationRequest::create([
            'complaint_id' => $s4->id,
            'requested_by' => $carlos->id,
            'question' => 'Can you tell us the usual collection time for that route, and whether the truck can back up to the health center gate?',
            'status' => 'open',
            'round' => 1,
        ]);
        $req->forceFill(['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)])->save();
        DemoData::activity($carlos, $menro, 'complaint.information_requested', $s4, "Resident was asked for more detail on {$s4->ticket_id}.", ['round' => 1], now()->subDays(5));

        // ── S5 · Sanitation, closed by the resident, with feedback ──
        $filed = now()->subDays(30);
        $p = DemoData::barangayPoint('Bagasbas');
        $s5 = DemoData::complaint([
            'user' => $maria,
            'department_code' => 'MENRO',
            'category' => 'sanitation',
            'title' => 'Clogged canal beside the public market',
            'description' => 'The open canal beside the public market has been choked with garbage and silt since the fiesta. Stagnant water is backing up onto the dry-goods row and the smell is unbearable in the afternoon.',
            'barangay' => 'Bagasbas',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Low',
            'status' => ComplaintStatus::Closed->value,
            'is_public' => true,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(28),
            'verified_at' => now()->subDays(28),
            'verifying_staff_id' => $carlos->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $carlos->id,
            'resolved_at' => now()->subDays(23),
            'closed_at' => now()->subDays(22),
            'closed_by' => 'resident',
            'filed_at' => $filed,
        ]);
        DemoData::log($s5, $maria, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to MENRO.', $filed);
        DemoData::log($s5, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on site — canal choked at two points; desilting scheduled.', now()->subDays(28));
        DemoData::log($s5, $carlos, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Crew with hand tools and a mini-excavator on site since Monday.', now()->subDays(25));
        DemoData::log($s5, $carlos, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Canal desilted and free-flowing; site cleared and treated against mosquito breeding.', now()->subDays(23));
        DemoData::log($s5, $maria, ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value, 'Resident confirmed the canal was cleared; closing the ticket.', now()->subDays(22));
        DemoData::activity($maria, $menro, 'complaint.filed', $s5, "Complaint {$s5->ticket_id} was filed and routed to MENRO.", [], $filed);
        DemoData::activity($carlos, $menro, 'complaint.reviewed', $s5, "{$s5->ticket_id} was verified after a site visit.", ['review_status' => 'verified'], now()->subDays(28));
        DemoData::activity($carlos, $menro, 'complaint.status_updated', $s5, "{$s5->ticket_id} moved to Resolved.", ['from' => 'In Progress', 'to' => 'Resolved'], now()->subDays(23));
        DemoData::activity($maria, $menro, 'complaint.closed_by_resident', $s5, "{$s5->ticket_id} was closed by the resident.", [], now()->subDays(22));

        // ── S6 · Plastic policy, privacy-gated, OM APPROVED for publication ──
        $filed = now()->subDays(20);
        $p = DemoData::barangayPoint('Barangay V (Pandan)');
        $s6 = DemoData::complaint([
            'user' => $gabriel,
            'department_code' => 'MENRO',
            'category' => 'plastic_usage',
            'title' => 'Sari-sari store along the national highway using single-use plastics',
            'description' => 'A sari-sari store along the national highway hands out single-use plastic bags for every purchase, including items meant to be consumed on the spot. This is contrary to the municipal plastic ordinance and has been going on for months.',
            'named_party' => 'Sari-sari store along the national highway, near the chapel',
            'barangay' => 'Barangay V (Pandan)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(18),
            'verified_at' => now()->subDays(18),
            'verifying_staff_id' => $carlos->id,
            'verification_method' => VerificationMethod::Desk->value,
            'assigned_staff_id' => $carlos->id,
            'resolved_at' => now()->subDays(12),
            'publication_status' => PublicationStatus::Approved->value,
            'publication_requested_at' => now()->subDays(10),
            'publication_requested_by' => $carlos->id,
            'publication_decided_at' => now()->subDays(8),
            'publication_decided_by' => $ana->id,
            'publication_note' => 'Approved for the public record with the business unnamed.',
            'redacted_title' => 'Retail store on national highway cited for single-use plastics',
            'redacted_description' => 'A retail establishment along the national highway was reported and verified for distributing single-use plastic products contrary to the municipal plastic ordinance. Corrective action was issued and confirmed.',
            'filed_at' => $filed,
        ]);
        DemoData::log($s6, $gabriel, null, ComplaintStatus::Submitted->value, 'Report of alleged plastic-policy violation filed and routed to MENRO. Filed private; the named party is held in the office.', $filed, true);
        DemoData::log($s6, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Notice of violation served on the establishment; response period of five days given.', now()->subDays(18), true);
        DemoData::log($s6, $carlos, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Establishment committed in writing to reusable alternatives. Follow-up inspection scheduled.', now()->subDays(14), true);
        DemoData::log($s6, $carlos, ComplaintStatus::InProgress->value, ComplaintStatus::Resolved->value, 'Follow-up inspection confirms compliance. Requested the Mayor\'s Office approve public release.', now()->subDays(12), true);
        DemoData::log($s6, $ana, $s6->status, $s6->status, 'Approved for public release with the establishment unnamed. The redacted record is published.', now()->subDays(8), true);
        DemoData::activity($gabriel, $menro, 'complaint.filed', $s6, "Complaint {$s6->ticket_id} was filed and routed to MENRO.", [], $filed);
        DemoData::activity($carlos, $menro, 'complaint.publication_requested', $s6, "MENRO requested public release of {$s6->ticket_id}.", ['category' => 'plastic_usage'], now()->subDays(10));
        DemoData::activity($ana, $om, 'complaint.publication_approved', $s6, "Mayor's Office approved public release of {$s6->ticket_id}.", ['category' => 'plastic_usage'], now()->subDays(8));

        // ── S7 · Plastic policy, privacy-gated, publication PENDING OM ──
        $filed = now()->subDays(9);
        $p = DemoData::barangayPoint('Barangay IV (Mantagbac)');
        $s7 = DemoData::complaint([
            'user' => $liza,
            'department_code' => 'MENRO',
            'category' => 'plastic_usage',
            'title' => 'Dry goods store in Mantagbac giving out plastic sachets and bags',
            'description' => 'A dry goods store in Barangay IV (Mantagbac) packs every purchase in plastic sachets and hands out plastic bags even for small amounts. Neighbors have raised this with the barangay but nothing has changed.',
            'named_party' => 'Dry goods retailer near the Mantagbac chapel',
            'barangay' => 'Barangay IV (Mantagbac)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Low',
            'status' => ComplaintStatus::Resolved->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(7),
            'verified_at' => now()->subDays(7),
            'verifying_staff_id' => $carlos->id,
            'verification_method' => VerificationMethod::Desk->value,
            'assigned_staff_id' => $carlos->id,
            'resolved_at' => now()->subDays(6),
            'publication_status' => PublicationStatus::Pending->value,
            'publication_requested_at' => now()->subDays(2),
            'publication_requested_by' => $carlos->id,
            // A request may only be asked for once the sensitive parts are
            // redacted; keep the demo data consistent with the rule the
            // publication panel and the request form enforce.
            'redacted_title' => 'Retail establishment in Barangay IV (Mantagbac) reported for plastic sachets',
            'redacted_description' => 'A retail establishment in Barangay IV (Mantagbac) was reported and verified for distributing plastic sachets and bags contrary to the municipal plastic ordinance. Corrective action was issued and is being confirmed.',
            'filed_at' => $filed,
        ]);
        DemoData::log($s7, $liza, null, ComplaintStatus::Submitted->value, 'Report of alleged plastic-policy violation filed and routed to MENRO. Filed private; the named party is held in the office.', $filed, true);
        DemoData::log($s7, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Notice of violation served; establishment explained it will phase out plastic sachets by December.', now()->subDays(7), true);
        DemoData::log($s7, $carlos, ComplaintStatus::UnderReview->value, ComplaintStatus::Resolved->value, 'Establishment committed to phasing out plastic sachets. Requested the Mayor\'s Office consider public release.', now()->subDays(6), true);
        DemoData::activity($liza, $menro, 'complaint.filed', $s7, "Complaint {$s7->ticket_id} was filed and routed to MENRO.", [], $filed);
        DemoData::activity($carlos, $menro, 'complaint.publication_requested', $s7, "MENRO requested public release of {$s7->ticket_id}.", ['category' => 'plastic_usage'], now()->subDays(2));

        // ── S8 · Water service out, routed to the Mayor's Office ──
        $filed = now()->subDays(5);
        $p = DemoData::barangayPoint('Cobangbang');
        $s8 = DemoData::complaint([
            'user' => $carmen,
            'department_code' => 'OM',
            'category' => 'others',
            'title' => 'Matagal nang hindi umaandar ang suplay ng tubig ng bayan sa Cobangbang',
            'description' => 'Dalawang linggo nang walang dumadaloy sa mga gripo ng Cobangbang. Ang konting tubig na nakakarating ay tuwing madaling araw lamang at marumi pa. Hindi namin alam kung kanino magpaparating dahil pribado ang serbisyo ng tubig.',
            'barangay' => 'Cobangbang',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'High',
            'status' => ComplaintStatus::UnderReview->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(4),
            'verified_at' => now()->subDays(4),
            'verifying_staff_id' => $admin->id,
            'verification_method' => VerificationMethod::Desk->value,
            'assigned_staff_id' => $ana->id,
            'filed_at' => $filed,
        ]);
        DemoData::log($s8, $carmen, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to the Mayor\'s Office.', $filed);
        DemoData::log($s8, $ana, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Forwarded for coordination with the private water service provider serving Cobangbang. The Mayor\'s Office will follow up on interconnection and supply schedules.', now()->subDays(4));
        DemoData::log($s8, $ana, ComplaintStatus::UnderReview->value, ComplaintStatus::UnderReview->value, 'Referral letter to the water service provider drafted; awaiting the Mayor\'s signature.', now()->subDays(2), true);
        DemoData::activity($carmen, $om, 'complaint.filed', $s8, "Complaint {$s8->ticket_id} was filed and routed to the Mayor's Office.", [], $filed);
        DemoData::activity($admin, $om, 'complaint.reviewed', $s8, "{$s8->ticket_id} was verified by the administrator.", ['review_status' => 'verified', 'method' => 'desk'], now()->subDays(4));

        // ── S9 · Duplicate pair: original (in progress) vs duplicate (frozen) ──
        $filed = now()->subDays(9);
        $p = DemoData::barangayPoint('Barangay VI (Centro)');
        $s9original = DemoData::complaint([
            'user' => $nena,
            'department_code' => 'MENRO',
            'category' => 'sanitation',
            'title' => 'Clogged drainage near the creek behind the market',
            'description' => 'The storm drainage running behind the market toward the creek is choked with trash and silt. After every rain, the backwater spreads across the back lane where the dry-goods vendors park.',
            'barangay' => 'Barangay VI (Centro)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(6),
            'verified_at' => now()->subDays(6),
            'verifying_staff_id' => $carlos->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $carlos->id,
            'filed_at' => $filed,
        ]);
        DemoData::log($s9original, $nena, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to MENRO.', $filed);
        DemoData::log($s9original, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on site — drainage choked at two points; desilting scheduled.', now()->subDays(6));
        DemoData::log($s9original, $carlos, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Crew and mini-excavator on site; clearing proceeds section by section.', now()->subDays(4));
        DemoData::activity($nena, $menro, 'complaint.filed', $s9original, "Complaint {$s9original->ticket_id} was filed and routed to MENRO.", [], $filed);
        DemoData::activity($carlos, $menro, 'complaint.status_updated', $s9original, "{$s9original->ticket_id} moved to In Progress.", ['from' => 'Under Review', 'to' => 'In Progress'], now()->subDays(4));

        $filed = now()->subDays(8);
        $s9duplicate = DemoData::complaint([
            'user' => $pedro,
            'department_code' => 'MENRO',
            'category' => 'sanitation',
            'title' => 'Clogged creek drainage near the market entrance',
            'description' => 'The drainage by the creek near the market entrance is blocked with garbage and mud. Rainwater has nowhere to go and keeps flooding the parking area.',
            'barangay' => 'Barangay VI (Centro)',
            'latitude' => $p[0] + 0.0004,
            'longitude' => $p[1] + 0.0003,
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'duplicate',
            'reviewed_by' => $carlos->id,
            'reviewed_at' => now()->subDays(7),
            'verifying_staff_id' => $carlos->id,
            'verification_method' => VerificationMethod::DuplicateMatch->value,
            'duplicate_of_id' => $s9original->id,
            'similarity_score' => 87.50,
            'filed_at' => $filed,
        ]);
        DemoData::log($s9duplicate, $pedro, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to MENRO.', $filed);
        DemoData::log($s9duplicate, $carlos, ComplaintStatus::Submitted->value, ComplaintStatus::Submitted->value, "Possible duplicate of {$s9original->ticket_id} (similarity 87.5%). Kept for the record; action proceeds on the original.", now()->subDays(7), true);
        DemoData::activity($carlos, $menro, 'complaint.moderation_flagged', $s9duplicate, "{$s9duplicate->ticket_id} was flagged as a possible duplicate of {$s9original->ticket_id}.", ['similarity_score' => 87.5], now()->subDays(7));

        // ── S10 · Spam, flagged for review before any office acts ──
        $filed = now()->subDays(3);
        $p = DemoData::barangayPoint('Barangay VIII (Salcedo)');
        $s10 = DemoData::complaint([
            'user' => $jose,
            'department_code' => 'OM',
            'category' => 'others',
            'title' => 'FREE MONEY!!! click here now!!!',
            'description' => 'FREE MONEY FREE MONEY click here https://gift-example.invalid/payday guaranteed profit urgent wire transfer 100 percent legit act now',
            'barangay' => 'Barangay VIII (Salcedo)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Low',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'pending',
            'spam_status' => 'review',
            'spam_score' => 91,
            'spam_reasons' => ['money_pattern', 'contains_url', 'excessive_keywords'],
            'filed_at' => $filed,
        ]);
        DemoData::log($s10, $jose, null, ComplaintStatus::Submitted->value, 'Complaint filed. Automated intake screening flagged it for review.', $filed);
        DemoData::activity(null, null, 'complaint.moderation_flagged', $s10, "{$s10->ticket_id} was flagged by automated intake screening.", ['spam_score' => 91], $filed);

        // ── S11 · Noise at the public plaza, escalated past the desk ──
        $filed = now()->subDays(6);
        $p = DemoData::barangayPoint('Barangay I (Hilahod)');
        $s11 = DemoData::complaint([
            'user' => $rodel,
            'department_code' => 'OM',
            'category' => 'others',
            'title' => 'Noise from the public plaza past midnight',
            'description' => 'Karaoke sound from the public plaza carries straight into the row of houses along Barangay I (Hilahod) past midnight every weekend. Residents asked the operator to keep it down but it has continued for three weeks.',
            'barangay' => 'Barangay I (Hilahod)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::UnderReview->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'escalated',
            'reviewed_by' => $ana->id,
            'reviewed_at' => now()->subDays(2),
            'verifying_staff_id' => $ana->id,
            'assigned_staff_id' => $ana->id,
            'review_notes' => 'Escalated beyond the standard desk for direct executive handling.',
            'filed_at' => $filed,
        ]);
        DemoData::log($s11, $rodel, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to the Mayor\'s Office.', $filed);
        DemoData::log($s11, $admin, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified at the desk; barangay mediation is warranted but the operator is not responding to the barangay captain.', now()->subDays(5));
        DemoData::log($s11, $ana, ComplaintStatus::UnderReview->value, ComplaintStatus::UnderReview->value, 'Escalated to the Executive Assistant to the Mayor for direct action.', now()->subDays(2), true);
        DemoData::activity($rodel, $om, 'complaint.filed', $s11, "Complaint {$s11->ticket_id} was filed and routed to the Mayor's Office.", [], $filed);
        DemoData::activity($ana, $om, 'complaint.reviewed', $s11, "{$s11->ticket_id} was escalated to executive handling.", ['review_status' => 'escalated'], now()->subDays(2));

        // ── S12 · Road work ON HOLD awaiting materials ──
        $filed = now()->subDays(25);
        $p = DemoData::barangayPoint('Bagasbas');
        $s12 = DemoData::complaint([
            'user' => $maria,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Uneven pavement and depression on the Bagasbas feeder road',
            'description' => 'A long depression has opened on the feeder road toward Bagasbas, right outside the basketball court. Motorcycles bottom out there in the rain and the edge keeps breaking away.',
            'barangay' => 'Bagasbas',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => now()->subDays(24),
            'verified_at' => now()->subDays(24),
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::SiteVisit->value,
            'assigned_staff_id' => $juan->id,
            'hold_reason' => HoldReason::AwaitingMaterials->value,
            'hold_note' => 'The crew reports the asphalt mix for this section is scheduled for delivery on November 20.',
            'hold_internal_note' => 'Supplier confirmed asphalt allocation for November 18; crewing up for November 21.',
            'hold_expected_resume_at' => '2026-11-20',
            'hold_started_at' => now()->subDays(6),
            'hold_started_by' => $juan->id,
            'hold_total_minutes' => 0,
            'filed_at' => $filed,
        ]);
        DemoData::log($s12, $maria, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to Engineering Office.', $filed);
        DemoData::log($s12, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Verified on site — depression measured at 40 cm; repair section staked.', now()->subDays(24));
        DemoData::log($s12, $juan, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Excavation completed; base re-compacted. Waiting on asphalt delivery.', now()->subDays(22));
        DemoData::log($s12, $juan, ComplaintStatus::InProgress->value, ComplaintStatus::InProgress->value, 'The repair is temporarily paused while we wait for asphalt materials, expected by November 20.', now()->subDays(6));
        DemoData::log($s12, $juan, ComplaintStatus::InProgress->value, ComplaintStatus::InProgress->value, 'Asphalt allocation confirmed for November 18; crew resumes November 21.', now()->subDays(6), true);
        DemoData::activity($maria, $eng, 'complaint.filed', $s12, "Complaint {$s12->ticket_id} was filed and routed to Engineering Office.", [], $filed);
        DemoData::activity($juan, $eng, 'complaint.put_on_hold', $s12, "{$s12->ticket_id} was put on hold: awaiting materials.", ['hold_reason' => 'awaiting_materials', 'expected_resume_at' => '2026-11-20'], now()->subDays(6));

        // ── S13 · Ask → answer → verified: the full information-request loop ──
        $filed = now()->subDays(8);
        $p = DemoData::barangayPoint('Barangay VIII (Salcedo)');
        $s13 = DemoData::complaint([
            'user' => $jose,
            'department_code' => 'ENG',
            'category' => 'road_damage',
            'title' => 'Pothole along the national road near the Salcedo junction',
            'description' => 'A pothole on the inbound lane near the Salcedo junction is deep enough to bottom out sedans. It was patched last month and has opened again in the same spot.',
            'barangay' => 'Barangay VIII (Salcedo)',
            'latitude' => $p[0],
            'longitude' => $p[1],
            'urgency' => 'Medium',
            'status' => ComplaintStatus::InProgress->value,
            'is_public' => false,
            'suggested_priority' => 'routine',
            'confirmed_priority' => 'routine',
            'review_status' => 'verified',
            'reviewed_by' => $verifier->id,
            'reviewed_at' => now()->subDays(6),
            'verified_at' => now()->subDays(6),
            'verifying_staff_id' => $verifier->id,
            'verification_method' => VerificationMethod::Desk->value,
            'assigned_staff_id' => $juan->id,
            'filed_at' => $filed,
        ]);
        DemoData::log($s13, $jose, null, ComplaintStatus::Submitted->value, 'Complaint filed and routed to Engineering Office.', $filed);
        DemoData::log($s13, $verifier, ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, 'Additional information requested from the resident.', now()->subDays(7));
        DemoData::log($s13, $jose, ComplaintStatus::UnderReview->value, ComplaintStatus::UnderReview->value, 'Resident provided additional details: inbound lane, just past the chapel gate.', now()->subDays(6));
        DemoData::log($s13, $verifier, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value, 'Verified at the desk against the barangay report; crew dispatched with patch material.', now()->subDays(6));
        $req = ComplaintInformationRequest::create([
            'complaint_id' => $s13->id,
            'requested_by' => $verifier->id,
            'question' => 'Which side of the road is the pothole on, and what is the nearest landmark?',
            'status' => 'answered',
            'round' => 1,
            'response_text' => 'Inbound lane, just past the chapel gate.',
            'responded_at' => now()->subDays(6),
        ]);
        $req->forceFill(['created_at' => now()->subDays(7), 'updated_at' => now()->subDays(6)])->save();
        DemoData::activity($verifier, $eng, 'complaint.information_requested', $s13, "Resident was asked for more detail on {$s13->ticket_id}.", ['round' => 1], now()->subDays(7));
        DemoData::activity($jose, $eng, 'complaint.information_provided', $s13, "Resident replied to the information request on {$s13->ticket_id}.", ['round' => 1], now()->subDays(6));
        DemoData::activity($juan, $eng, 'complaint.status_updated', $s13, "{$s13->ticket_id} moved to In Progress.", ['from' => 'Under Review', 'to' => 'In Progress'], now()->subDays(6));

        $this->command->info('Handcrafted demo complaints completed (14 tickets).');
    }
}
