<?php

// config/auth_web.php
return [
    /*
    |--------------------------------------------------------------------------
    | Login Rate Limiting
    |--------------------------------------------------------------------------
    | attempts       : how many failed attempts allowed before lockout
    | decay_minutes  : how many minutes until attempts reset
    */
    'login' => [
        'attempts' => env('AUTH_WEB_LOGIN_ATTEMPTS', 5),
        'decay_minutes' => env('AUTH_WEB_LOGIN_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Register Rate Limiting
    |--------------------------------------------------------------------------
    | Prevents spam account creation from a single IP.
    */
    'register' => [
        'attempts' => env('AUTH_WEB_REGISTER_ATTEMPTS', 3),
        'decay_minutes' => env('AUTH_WEB_REGISTER_DECAY', 10),
    ],
];
