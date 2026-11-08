<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The keys are the values stored in the session, cookie, and users.locale
    | column. The values are what the language switcher shows to residents.
    |
    | Filipino uses the ISO 639-1 code "fil" rather than "tl". The language
    | is officially Filipino, and the code keeps room for a future Bikol
    | ("bcl") option without renaming anything.
    |
    */
    'supported' => [
        'en' => 'English',
        'fil' => 'Filipino',
    ],

    'default' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Guest preference cookie
    |--------------------------------------------------------------------------
    |
    | A resident who switches language before signing in should keep that
    | choice after the session ends. One year is long enough to be useful
    | without being permanent.
    |
    */
    'cookie' => 'locale',
    'cookie_lifetime' => 60 * 24 * 365,
];
