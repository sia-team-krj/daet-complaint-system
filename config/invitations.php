<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Staff Invitation Lifetime
    |--------------------------------------------------------------------------
    |
    | Emailed invitations have to survive a real inbox round trip, so the
    | window is measured in hours rather than minutes. Every code is still
    | single use, revocable, and locked to the address it was sent to.
    |
    */

    'expires_in_hours' => (int) env('INVITATION_EXPIRES_IN_HOURS', 24),
];
