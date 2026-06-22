<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Calendar OAuth 2.0 Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials obtained from the Google Cloud Console under
    | APIs & Services > Credentials > OAuth 2.0 Client ID (Web application).
    |
    */
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Default timezone for Google Calendar events.
    |--------------------------------------------------------------------------
    */
    'timezone' => env('GOOGLE_CALENDAR_TIMEZONE', 'America/Argentina/Buenos_Aires'),

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 scopes requested during authorization.
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        \Google\Service\Calendar::CALENDAR_EVENTS,
    ],

    /*
    |--------------------------------------------------------------------------
    | Access type for OAuth. 'offline' returns a refresh token so the
    | integration works without the user re-authorizing every hour.
    |--------------------------------------------------------------------------
    */
    'access_type' => 'offline',

    /*
    |--------------------------------------------------------------------------
    | Prompt for consent. 'consent' forces Google to return a refresh token
    | on every authorization. Required by Google for 'offline' access.
    |--------------------------------------------------------------------------
    */
    'prompt' => 'consent',
];
