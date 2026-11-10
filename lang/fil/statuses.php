<?php

return [
    'complaint_status' => [
        'submitted' => 'Isinumite',
        'under_review' => 'Sinusuri',
        'in_progress' => 'Isinasagawa',
        'resolved' => 'Nalutas',
        'rejected' => 'Tinanggihan',
        'closed' => 'Sarado',
    ],

    'review_status' => [
        'pending' => 'Naghihintay ng beripikasyon',
        'verified' => 'Na-verify',
        'needs_information' => 'Kailangan ng impormasyon',
        'duplicate' => 'Duplicate',
        'rejected' => 'Tinanggihan',
        'escalated' => 'Na-escalate',
    ],

    'priority' => [
        'routine' => 'Karaniwan',
        'elevated' => 'Nakaangat',
        'urgent' => 'Urgente',
        'critical' => 'Kritikal',
    ],

    'information_request' => [
        'open' => 'Naghihintay sa residente',
        'answered' => 'Sumagot ang residente',
        'resolved' => 'Sarado',
    ],

    'verification_method' => [
        'desk' => 'Sinuri ang ulat',
        'site_visit' => 'Binista ang lugar',
        'duplicate_match' => 'Naiulat na',
    ],

    // What kind of work a complaint is. It decides what counts as proof:
    // a repair is proven by a photograph of it, an enforcement action by a
    // written record. See config/complaint_work_policy.php.
    'work_mode' => [
        'labor_required' => 'Trabaho',
        'report_only' => 'Ulat lamang',
        'referral' => 'Ipinasa',
    ],

    // Where a complaint stands against its deadline. The office owes work
    // from the moment it verifies a report.
    'sla' => [
        'on_track' => 'Nasa track',
        'due_soon' => 'Malapit na takdang',
        'overdue' => 'Na-lampas',
        'met' => 'Tapos na',
        'not_applicable' => 'Hindi pa beripika',
    ],
];
