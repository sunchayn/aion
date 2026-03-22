<?php

namespace Tests\App\Modules\Auth\Services\TwoFactorAuth;

use App\Modules\Auth\Services\TwoFactorAuth\Drivers\GoogleTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(TwoFactorAuthManager::class)]
#[Group('authentication')]
#[Group('2fa')]
class TwoFactorAuthManagerFunctionalTest extends FunctionalTestCase
{
    public function test_it_can_instantiate_google_driver(): void
    {
        // Arrange

        $manager = new TwoFactorAuthManager($this->app);

        // Act

        $driver = $manager->driver('google');

        // Assert

        $this->assertInstanceOf(GoogleTwoFactorAuth::class, $driver);
    }

    public function test_it_uses_default_driver_from_config(): void
    {
        // Arrange

        config(['two-factor-auth.default' => 'google']);

        $manager = new TwoFactorAuthManager($this->app);

        // Act

        $driver = $manager->driver();

        // Assert

        $this->assertInstanceOf(GoogleTwoFactorAuth::class, $driver);
    }
}
