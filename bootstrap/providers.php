<?php

use App\Modules\Auth\Providers\AuthRelationshipsServiceProvider;
use App\Modules\Auth\Providers\TwoFactorAuthenticationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AuthRelationshipsServiceProvider::class,
    TwoFactorAuthenticationServiceProvider::class,
];
