<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'oauth' => [
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
            'redirect_url' => env('GOOGLE_REDIRECT_URL', ''),
            'scopes' => 'openid profile email',
        ],
    ],

    'apple' => [
        'oauth' => [
            'redirect_url' => env('APPLE_REDIRECT_URL'),
            'client_id' => env('APPLE_CLIENT_ID'),
            'client_secret' => env('APPLE_CLIENT_SECRET'),
            'team_id' => env('APPLE_TEAM_ID'),
            'key_id' => env('APPLE_KEY_ID'),
            'private_key' => base64_decode((string) env('APPLE_PRIVATE_KEY_BASE64', '')),
        ],
    ],

    'github' => [
        'oauth' => [
            'client_id' => env('GITHUB_CLIENT_ID', ''),
            'client_secret' => env('GITHUB_CLIENT_SECRET', ''),
            'redirect_url' => env('GITHUB_REDIRECT_URL', ''),
        ],
    ],

];
