<?php

return [
    'page' => [
        'title' => 'Complaint :ticket - Daet Listens',
        'back' => 'My complaints',
        'reference_label' => 'Reference number',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'filed_with_office' => 'Filed :date · :office',
        'filed_only' => 'Filed :date',
    ],

    'progress' => [
        'title' => 'Progress',
        'current' => 'Current',
        'completed' => 'Completed',
        'upcoming' => 'Upcoming',
        'pending' => 'Pending',
        'next_title' => 'What happens next',
        'resolved_on' => 'Resolved on :date',
        'closed_on' => 'Closed on :date',
        'rejected_on' => 'Stopped on :date',
        'date_unavailable' => '—',
    ],

    'report' => [
        'title' => 'Your report',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'counter' => ':current / :total',
        'viewer_close' => 'Close photo viewer',
        'viewer_label' => 'Photo viewer',
        'photo_alt' => 'Complaint photo :index',
        'no_photos' => 'No photos attached to this complaint',
        'description_label' => 'Description',
        'photos_label' => 'Attached Photos',
    ],

    'location' => [
        'title' => 'Location',
        'address_label' => 'Address',
        'open_in_maps' => 'Open in Maps',
        'map_unavailable' => 'Map unavailable. Your coordinates are still listed below.',
        'no_location' => 'No location data available',
    ],

    'details' => [
        'title' => 'Report details',
        'category' => 'Category',
        'department' => 'Department',
        'review_status' => 'Review status',
        'suggested_priority' => 'Suggested priority',
        'confirmed_priority' => 'Confirmed priority',
        'published' => 'Published on the transparency page',
        'date_filed' => 'Date Filed',
        'awaiting' => 'Awaiting department review',
        'unassigned' => 'Unassigned',
        'code_label' => 'Code',
        'legacy_details' => 'Details',
    ],

    'updates' => [
        'title' => 'Updates',
        'responded_by' => 'Responded by :office',
        'staff_responses' => 'Staff Responses',
    ],

    'visibility' => [
        'title' => 'Who can see this report',
        'title_legacy' => 'Who can see this',
        'lede' => 'You decide whether this appears on the public transparency page. No member of staff can change it.',
        'how_it_works' => 'How this works',
        'publish' => 'Publish on the transparency page',
        'privatise' => 'Make this private',
        'public_state' => 'Currently published under your display alias.',
        'private_state' => 'Currently private. Only you and the staff handling it can see this.',
    ],

    'help' => [
        'title' => 'Need help?',
    ],

    'action' => [
        'title_open' => 'The department needs more information',
        'title_answered' => 'We received your reply',
        'kicker' => 'Waiting on you',
        'lede' => 'The office reviewing this complaint needs more from you before it can proceed. Answer the question below — photos help confirm the spot.',
        'fallback_question' => 'The office needs more information to finish the review of this complaint.',
        'from_office' => 'From the reviewing office',
        'asked_by' => 'Asked by :name · :time',
        'asked_by_department' => 'Asked by the department · :time',
        'thanks' => 'Thank you. The department has your reply and will continue the review.',
        'reply_label' => 'Your reply',
        'reply_placeholder' => 'Describe what you can see, or add anything that would help the department check this.',
        'photos_label' => 'Add photos',
        'photos_optional' => 'Optional, up to 5',
        'photos_hint' => 'Photos taken on your phone may share their location, which helps us confirm the spot.',
        'submit' => 'Send to the department',
    ],

    'close' => [
        'kicker' => 'Resolved',
        'title' => 'Is this issue resolved for you?',
        'lede' => 'The department marked this report as resolved. If everything is settled, close the report to confirm. If you do not close it, it will close automatically after 7 days.',
        'submit' => 'Close this report',
        'success' => 'Thank you. Your report is now closed.',
        'log_comment' => 'Resident confirmed the resolution and closed this report.',
    ],
];
