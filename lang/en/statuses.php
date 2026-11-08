<?php

return [
    'complaint_status' => [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
        'closed' => 'Closed',
    ],

    'review_status' => [
        'pending' => 'Pending verification',
        'verified' => 'Verified',
        'needs_information' => 'Needs information',
        'duplicate' => 'Duplicate',
        'rejected' => 'Rejected',
        'escalated' => 'Escalated',
    ],

    'priority' => [
        'routine' => 'Routine',
        'elevated' => 'Elevated',
        'urgent' => 'Urgent',
        'critical' => 'Critical',
    ],

    'information_request' => [
        'open' => 'Waiting on resident',
        'answered' => 'Resident replied',
        'resolved' => 'Closed',
    ],

    'verification_method' => [
        'desk' => 'Report reviewed',
        'site_visit' => 'Site attended',
        'duplicate_match' => 'Already reported',
    ],
];
