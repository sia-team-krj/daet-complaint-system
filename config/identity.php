<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resident identity verification
    |--------------------------------------------------------------------------
    | When true, citizens must verify their identity before filing a new
    | complaint. Existing reports, information requests, closure, feedback and
    | visibility changes remain available regardless of this flag.
    */
    'verification_required' => (bool) env('IDENTITY_VERIFICATION_REQUIRED', env('APP_ENV') === 'production'),

    /*
    | HMAC key for duplicate-ID detection. Separate from APP_KEY so rotating
    | APP_KEY does not silently invalidate existing hashes.
    */
    'hash_key' => env('IDENTITY_HASH_KEY'),

    /*
    | Accepted government-issued IDs. One fallback, Barangay certificate.
    */
    'id_types' => [
        'PhilID',
        'PHTIN',
        'PRC Card (Professional/Special)',
        'Authentication',
        'National ID',
        'Other Government ID',
    ],
    'accepted_ids' => ['PhilID', 'PHTIN', 'PRC Card (Professional/Special)', 'Authentication', 'National ID', 'Other Government ID', 'Barangay Certificate'],

    /*
    | File handling: image sizes, retention, and purge proof.
    */
    'id_front_path' => 'identity/{userId}/{uuid}.enc',
    'max_upload_bytes' => 5 * 1024 * 1024,
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    'max_dimension' => 2400,
    'purge_orphans_after_hours' => 24,

    /*
    | Submission limits.
    */
    'max_submissions_per_day' => 3,
];
