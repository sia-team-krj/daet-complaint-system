<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Administrator two-factor authentication
    |--------------------------------------------------------------------------
    |
    | TOTP is required on every admin route in production. Local and testing
    | default to off so a fresh database does not lock the developer out of
    | the workspace before they can enroll. Set ADMIN_2FA_REQUIRED=true in
    | staging and production.
    |
    */
    'two_factor' => [
        'required' => (bool) env('ADMIN_2FA_REQUIRED', env('APP_ENV', 'production') === 'production'),
        'issuer' => (string) env('ADMIN_2FA_ISSUER', env('APP_NAME', 'Daet Listens')),
        'window' => 1,
        'recovery_codes' => 8,
        'challenge_minutes' => 10,
        'max_attempts' => 5,
    ],
];
