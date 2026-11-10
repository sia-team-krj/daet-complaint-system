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

    // What kind of work a complaint is. It decides what counts as proof:
    // a repair is proven by a photograph of it, an enforcement action by a
    // written record. See config/complaint_work_policy.php.
    'work_mode' => [
        'labor_required' => 'Labor work',
        'report_only' => 'Report only',
        'referral' => 'Referral',
    ],

    // Where a complaint stands against its deadline. The office owes work
    // from the moment it verifies a report.
    'sla' => [
        'on_track' => 'On track',
        'due_soon' => 'Due soon',
        'overdue' => 'Overdue',
        'met' => 'Met',
        'not_applicable' => 'Not verified yet',
    ],
];
