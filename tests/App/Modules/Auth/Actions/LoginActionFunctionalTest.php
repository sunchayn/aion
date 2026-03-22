<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\StatefulLoginAction;
use App\Modules\Auth\Actions\StatelessLoginAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
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

#[CoversClass(LoginAction::class)]
#[CoversClass(LoginData::class)]
#[Group('authentication')]
class LoginActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_executes_stateful_action_when_guard_driver_is_session(): void
    {
        // Arrange

        $user = User::factory()->create();
        $guard = '::stateful_guard::';
        $remember = fake()->boolean();

        $authManagerMock = $this->mock(AuthManager::class);
        $statefulActionMock = $this->spy(StatefulLoginAction::class);
        $statelessActionSpy = $this->spy(StatelessLoginAction::class);

        $loginAction = new LoginAction(
            statelessLoginAction: $statelessActionSpy,
            statefulLoginAction: $statefulActionMock,
            authManager: $authManagerMock,
        );

        // Anticipate

        $authManagerMock
            ->expects('guard')
            ->with($guard)
            ->andReturn(
                Mockery::mock(StatefulGuard::class), // <- A dummy "stateful" mock.
            );

        $statefulActionMock
            ->expects('execute')
            ->withAnyArgs()
            ->andReturn(
                $expectedResponse = Mockery::mock(SessionLoginResponse::class)
            );

        // Act

        $actualResponse = $loginAction
            ->execute(
                data: new LoginData(
                    $user,
                    $guard,
                    $remember,
                ),
            );

        // Assert

        $this->assertSame($expectedResponse, $actualResponse);

        $statelessActionSpy->shouldNotHaveReceived('execute');

        $statefulActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (LoginData $dataArg) use ($user, $guard, $remember): bool {
                $this->assertTrue(
                    $dataArg->user->is($user),
                );

                $this->assertEquals(
                    $guard,
                    $dataArg->guard,
                );

                $this->assertEquals(
                    $remember,
                    $dataArg->remember,
                );

                return true;
            })
            ->once();

    }

    public function test_it_executes_stateless_action_when_guard_driver_is_not_session(): void
    {
        // Arrange

        $user = User::factory()->create();
        $guard = '::stateless_guard::';
        $remember = fake()->boolean();

        $authManagerMock = $this->mock(AuthManager::class);
        $statefulActionSpy = $this->spy(StatefulLoginAction::class);
        $statelessActionMock = $this->spy(StatelessLoginAction::class);

        $loginAction = new LoginAction(
            statelessLoginAction: $statelessActionMock,
            statefulLoginAction: $statefulActionSpy,
            authManager: $authManagerMock,
        );

        // Anticipate

        $authManagerMock
            ->expects('guard')
            ->with($guard)
            ->andReturn(
                Mockery::mock(Guard::class), // <- An ordinary guard, not stateful.
            );

        $statelessActionMock
            ->expects('execute')
            ->withAnyArgs()
            ->andReturn(
                $expectedResponse = Mockery::mock(TokenLoginResponse::class),
            );

        // Act

        $actualResponse = $loginAction
            ->execute(
                data: new LoginData(
                    $user,
                    $guard,
                    $remember,
                ),
            );

        // Assert

        $this->assertSame($expectedResponse, $actualResponse);

        $statefulActionSpy->shouldNotHaveReceived('execute');

        $statelessActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (LoginData $dataArg) use ($user, $guard, $remember): bool {
                $this->assertTrue(
                    $dataArg->user->is($user),
                );

                $this->assertEquals(
                    $guard,
                    $dataArg->guard,
                );

                $this->assertEquals(
                    $remember,
                    $dataArg->remember,
                );

                return true;
            })
            ->once();
    }
}
