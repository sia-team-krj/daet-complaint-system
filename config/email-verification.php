<?php

return [
    'expires_in_minutes' => (int) env('EMAIL_OTP_EXPIRES_MINUTES', 10),
    'max_attempts' => (int) env('EMAIL_OTP_MAX_ATTEMPTS', 5),

    // Minimum gap between two "send a new code" requests for the same account.
    'resend_cooldown_seconds' => (int) env('EMAIL_OTP_RESEND_COOLDOWN_SECONDS', 60),

    // Ceiling on top of the cooldown. The cooldown alone still permits 60 codes
    // an hour to one account, which is enough to fill an inbox and burn mail
    // quota, so there is a separate longer window as well.
    'resend_max_per_hour' => (int) env('EMAIL_OTP_RESEND_MAX_PER_HOUR', 10),
];
