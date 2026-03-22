<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\StatefulLoginAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Hooks\BeforeLoginTwoFactorHook;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Responses\SessionLoginResponse;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(StatefulLoginAction::class)]
#[Group('authentication')]
class StatefulLoginActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_logs_in_user_and_returns_session_response(): void
    {
        // Arrange

        $user = User::factory()->create();
        $guardName = '::guard::';
        $remember = fake()->boolean();

        $authManagerMock = $this->mock(AuthManager::class);
        $dispatcherSpy = $this->spy(Dispatcher::class);

        // Anticipate

        $authManagerMock
            ->shouldReceive('guard')
            ->once()
            ->with($guardName)
            ->andReturnSelf();

        $authManagerMock->expects('login');

        // Act

        $loggedInUser = resolve(StatefulLoginAction::class)
            ->execute(
                new LoginData($user, $guardName, $remember),
            );

        // Assert

        $this->assertEquals($user->id, $loggedInUser->user->id);
        $this->assertFalse($loggedInUser->twoFactor);
        $this->assertNull($loggedInUser->twoFactorToken);

        $authManagerMock
            ->shouldHaveReceived('login')
            ->withArgs(function (User $userArg, bool $rememberArg) use ($user, $remember) {
                $this->assertTrue(
                    $user->is($userArg),
                );

                $this->assertEquals(
                    $remember,
                    $rememberArg,
                );

                return true;
            });

        $dispatcherSpy
            ->shouldHaveReceived('dispatch')
            ->once()
            ->withArgs(function (Login $event) use ($user, $guardName, $remember): bool {
                $this->assertTrue(
                    $event->user->is($user),
                );

                $this->assertEquals(
                    $guardName,
                    $event->guard,
                );

                $this->assertEquals(
                    $remember,
                    $event->remember,
                );

                return true;
            });
    }

    public function test_it_returns_two_factor_session_response_if_2fa_enabled(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->confirmed()
            ->for($user)
            ->create();

        $guardName = '::guard::';

        $authManagerMock = $this->mock(AuthManager::class);
        $dispatcherSpy = $this->spy(Dispatcher::class);

        $twoAuthResponseStub = SessionLoginResponse::pendingTwoFactorAuth($user, '::2fa_token::');

        $beforeLoginHookStub = new class($twoAuthResponseStub)
        {
            public function __construct(
                private readonly SessionLoginResponse $responseStub,
            ) {}

            public function __invoke()
            {
                return $this->responseStub;
            }
        };

        $this->instance(
            BeforeLoginTwoFactorHook::class,
            $beforeLoginHookStub,
        );

        // Anticipate

        $authManagerMock
            ->shouldReceive('guard')
            ->with($guardName)
            ->andReturn(
                $guardMock = Mockery::mock(StatefulGuard::class),
            );

        $guardMock->shouldReceive('login')->never();

        // Act

        $actualResponse = resolve(StatefulLoginAction::class)
            ->execute(
                new LoginData($user, $guardName),
            );

        // Assert

        $this->assertSame(
            $twoAuthResponseStub,
            $actualResponse,
        );

        $this->assertTrue(
            $actualResponse->getUser()->is($user),
        );

        $dispatcherSpy->shouldNotHaveReceived('dispatch');
    }
}
