<?php

namespace App\Modules\Auth\Services\TwoFactorAuth;

use App\Modules\Auth\Services\TwoFactorAuth\Drivers\GoogleTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\Drivers\TwoFactorAuthProviderContract;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Manager;
use PragmaRX\Google2FA\Google2FA;

/**
 * @mixin TwoFactorAuthProviderContract
 */
class TwoFactorAuthManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param  string|null  $driver
     */
    public function driver($driver = null): TwoFactorAuthProviderContract
    {
        return parent::driver($driver);
    }

    public function createGoogleDriver(): TwoFactorAuthProviderContract
    {
        return new GoogleTwoFactorAuth(
            new Google2FA,
            $this->container->make(Repository::class),
        );
    }

    public function getDefaultDriver(): string
    {
        return $this->container->make(Repository::class)->get('two-factor-auth.default', 'google');
    }
}
