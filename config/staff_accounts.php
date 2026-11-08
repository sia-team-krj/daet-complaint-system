<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Temporary Staff Password Lifetime
    |--------------------------------------------------------------------------
    |
    | Accounts created with an emailed or manually set temporary password
    | (options B and C on the staff creation form) must change that password
    | on first login. The temporary password itself stops working after
    | this many hours, after which an admin must issue a new one or an
    | invitation. Configurable via TEMP_PASSWORD_EXPIRES_IN_HOURS.
    |
    */
    'temp_password_expires_in_hours' => (int) env('TEMP_PASSWORD_EXPIRES_IN_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Invitation Expiry Options (hours)
    |--------------------------------------------------------------------------
    |
    | The expiry choices offered on the staff creation form for the
    | invitation method. The form defaults to 48 hours.
    |
    */
    'invitation_expires_options' => [24, 48, 168],
    'invitation_expires_default' => 48,
];
