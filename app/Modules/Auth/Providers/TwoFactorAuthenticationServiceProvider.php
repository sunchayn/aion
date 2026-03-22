<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Hooks\BeforeLoginTwoFactorHook;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TwoFactorAuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(fn (Application $app): TwoFactorAuthManager => new TwoFactorAuthManager($app));

        $this->app->tag(
            [
                BeforeLoginTwoFactorHook::class,
            ],
            'hooks.login.before',
        );
    }
}
