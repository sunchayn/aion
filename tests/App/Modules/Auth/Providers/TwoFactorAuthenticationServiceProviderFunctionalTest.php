<?php

namespace Tests\App\Modules\Auth\Providers;

use App\Modules\Auth\Providers\TwoFactorAuthenticationServiceProvider;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(TwoFactorAuthenticationServiceProvider::class)]
#[Group('authentication')]
#[Group('2fa')]
class TwoFactorAuthenticationServiceProviderFunctionalTest extends FunctionalTestCase
{
    public function test_it_registers_manager_as_singleton(): void
    {
        // Act

        $instance1 = resolve(TwoFactorAuthManager::class);
        $instance2 = resolve(TwoFactorAuthManager::class);

        // Assert

        $this->assertInstanceOf(TwoFactorAuthManager::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }
}
