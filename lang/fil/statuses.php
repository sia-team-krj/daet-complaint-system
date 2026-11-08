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
];
