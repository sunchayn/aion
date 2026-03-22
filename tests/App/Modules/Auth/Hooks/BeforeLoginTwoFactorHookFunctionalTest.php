<?php

namespace Tests\App\Modules\Auth\Hooks;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Hooks\BeforeLoginTwoFactorHook;
use App\Modules\Auth\Responses\SessionLoginResponse;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(BeforeLoginTwoFactorHook::class)]
#[Group('authentication')]
class BeforeLoginTwoFactorHookFunctionalTest extends FunctionalTestCase
{
    public function test_it_returns_null_if_2fa_is_not_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $actualResponse = resolve(BeforeLoginTwoFactorHook::class)(
            new LoginData($user, '::guard::'),
        );

        // Assert

        $this->assertNull($actualResponse);
    }

    public function test_it_returns_null_if_skip_two_factor_is_true(): void
    {
        // Arrange

        $user = User::factory()
            ->hasTwoFactorAuth(['confirmed_at' => now()])
            ->create();

        // Act

        $actualResponse = resolve(BeforeLoginTwoFactorHook::class)(
            new LoginData($user, '::guard::', skipTwoFactor: true),
        );

        // Assert

        $this->assertNull($actualResponse);
    }

    public function test_it_returns_session_response_for_stateful_guard_with_2fa(): void
    {
        // Arrange

        $user = User::factory()
            ->hasTwoFactorAuth(['confirmed_at' => now()])
            ->create();

        $guardName = '::stateful_guard::';

        $authManagerMock = $this->mock(AuthManager::class);

        // Anticipate

        $authManagerMock
            ->shouldReceive('guard')
            ->once()
            ->with($guardName)
            ->andReturn(Mockery::mock(StatefulGuard::class));

        // Act

        $actualResponse = resolve(BeforeLoginTwoFactorHook::class)(
            new LoginData($user, $guardName),
        );

        // Assert

        $this->assertInstanceOf(SessionLoginResponse::class, $actualResponse);
        $this->assertTrue($actualResponse->twoFactor);
        $this->assertNotNull($actualResponse->twoFactorToken);
    }

    public function test_it_returns_token_response_for_stateless_guard_with_2fa(): void
    {
        // Arrange

        $user = User::factory()
            ->hasTwoFactorAuth(['confirmed_at' => now()])
            ->create();

        $guardName = '::stateless_guard::';

        $authManagerMock = $this->mock(AuthManager::class);

        // Anticipate

        $authManagerMock
            ->shouldReceive('guard')
            ->once()
            ->with($guardName)
            ->andReturn(Mockery::mock(Guard::class));

        // Act

        $actualResponse = resolve(BeforeLoginTwoFactorHook::class)(
            new LoginData($user, $guardName),
        );

        // Assert

        $this->assertInstanceOf(TokenLoginResponse::class, $actualResponse);
        $this->assertTrue($actualResponse->twoFactor);
        $this->assertNotNull($actualResponse->twoFactorToken);
    }
}
