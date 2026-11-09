<?php

return [
    'page' => [
        'title' => 'Complaint submitted · :ticket',
    ],

    'hero' => [
        'title' => 'Your complaint has been submitted',
        'lede_office' => 'Routed to :office. You can follow every step from :menu.',
        'lede_pending' => 'Your report is in the queue. We will route it to the right office shortly.',
    ],

    'ticket' => [
        'label' => 'Ticket number',
        'hint' => 'Save this number — you will need it to track the report.',
    ],

    'facts' => [
        'status' => 'Status',
        'filed' => 'Filed',
        'routed_to' => 'Routed to',
        'photos' => 'Photos',
        'photos_count' => ':count photo|:count photos',
    ],

    'next' => [
        'title' => 'What happens next',
        'location_gps' => 'The location embedded in your photo was used to place the report.',
        'location_plain' => 'Your address or map point was used as the report location.',
        'location_not_applicable' => 'Location was not required for this type of report. If the office needs it, they may ask in a follow-up.',
        'location_none' => 'No photo location was found. The office may ask for more details during review.',
    ],

    'actions' => [
        'view' => 'View your report',
        'my_complaints' => 'Go to My Complaints',
    ],

    'redirect' => [
        'countdown' => 'Redirecting to :menu in {s} s',
        'cancel' => 'Stay on this page',
    ],
];
