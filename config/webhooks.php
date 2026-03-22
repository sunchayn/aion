<?php

return [

    // @todo add comments to explain this config.

    'frontend' => [

        /*
        |--------------------------------------------------------------------------
        | Front-end URL
        |--------------------------------------------------------------------------
        |
        |
        | This URL is used by the API to properly generate front-end URLs when
        | sending emails such as password reset links. You should set this to the
        | root of the front-end application.
        |
        */

        'url' => $frontEndUrl = env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

        'redirects' => [
            'password_reset_url' => "{$frontEndUrl}/password_reset",
            'magic_link_url' => "{$frontEndUrl}/magic-link",
            'email_verification_notice' => "{$frontEndUrl}/verify/email",
        ],
    ],
];
