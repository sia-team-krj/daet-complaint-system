<?php

return [
    'title' => 'Work queue - Daet Listens',
    'queue' => 'Complaint queue',
    'banner_sub' => 'Every complaint routed to your office, newest triage order first.',
    'empty_title' => 'The queue is clear',
    'empty_desc' => 'Nothing matches these filters right now.',
    'showing' => ':count :items found · :per page',
    'complaints_found' => '{1} 1 complaint found|[2,*] :count complaints found',
    'back_to_queue' => 'Back to queue',
    'review' => 'Review',
    'stats' => [
        'pending_review' => 'Pending review',
        'in_progress' => 'In progress',
        'resolved_this_month' => 'Resolved this month',
        'avg_days_open' => 'Average days open',
    ],
];
