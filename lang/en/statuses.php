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

    // The office's two reasons for marking a report misleading or spam.
    // Language-neutral: translated only on display.
    'complaint_marking' => [
        'misleading' => 'Misleading',
        'spam' => 'Spam',
    ],

    'review_status' => [
        'pending' => 'Pending verification',
        'verified' => 'Verified',
        'needs_information' => 'Needs information',
        'duplicate' => 'Duplicate',
        'rejected' => 'Rejected',
        'escalated' => 'Escalated',
    ],

    'feedback_status' => [
        'published' => 'Published',
        'pending' => 'Waiting to be read',
        'withheld' => 'Kept off the register',
    ],

    'publication_status' => [
        'pending' => "Awaiting the Mayor's office",
        'approved' => 'Approved for publication',
        'declined' => 'Not published — privacy concern',
    ],

    // Why an office has paused the work on a complaint.
    'hold_reason' => [
        'awaiting_funds' => 'Waiting on funds',
        'awaiting_materials' => 'Waiting on materials',
        'awaiting_procurement' => 'Waiting on procurement',
        'awaiting_budget_approval' => 'Waiting on a budget decision',
        'awaiting_other_office' => 'Waiting on another office',
        'awaiting_weather' => 'Waiting on the weather',
        'other' => 'Another reason',
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
        'on_hold' => 'On hold — the clock is paused',
    ],
];
