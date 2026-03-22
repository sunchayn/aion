<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Two-Factor Authentication Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default two-factor authentication driver that
    | will be used by the application. You may set this to any of the
    | drivers defined in the "providers" array below.
    |
    */

    'default' => env('TWO_FACTOR_AUTH_DRIVER', 'google'),

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication providers
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the two-factor authentication providers for
    | your application. A default configuration has been defined for
    | each provider to get you started.
    |
    */

    'providers' => [

        'google' => [
            'driver' => 'google',
        ],

    ],

];
