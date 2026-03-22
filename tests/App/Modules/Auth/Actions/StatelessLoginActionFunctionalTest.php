<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\StatelessLoginAction;
use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Hooks\BeforeLoginTwoFactorHook;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(StatelessLoginAction::class)]
#[Group('authentication')]
class StatelessLoginActionFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenJwtIsNotAvailable();
    }

    public function test_it_logs_in_user_and_returns_token(): void
    {
        // Arrange

        $user = User::factory()->create();
        $guardName = '::stateless_guard::';
        $authToken = 'auth-token-123';
        $refreshToken = 'refresh-token-456';

        $tokenIssuerMock = $this->mock(AuthTokenIssuerContract::class);
        $dispatcherSpy = $this->spy(Dispatcher::class);

        // Anticipate

        $tokenIssuerMock
            ->shouldReceive('useGuard')
            ->once()
            ->with($guardName)
            ->andReturnSelf();

        $tokenIssuerMock
            ->shouldReceive('issue')
            ->once()
            ->with($user)
            ->andReturn(
                new IssuedTokens(
                    authToken: $authToken,
                    refreshToken: $refreshToken,
                ),
            );

        // Act

        $actualResponse = resolve(StatelessLoginAction::class)
            ->execute(
                new LoginData($user, $guardName),
            );

        // Assert

        $this->assertEquals($user->id, $actualResponse->getUser()->id);
        $this->assertSame($authToken, $actualResponse->authToken);
        $this->assertSame($refreshToken, $actualResponse->refreshToken);
        $this->assertFalse($actualResponse->twoFactor);

        $dispatcherSpy
            ->shouldHaveReceived('dispatch')
            ->once()
            ->withArgs(function (Login $event) use ($user, $guardName): bool {
                $this->assertTrue(
                    $event->user->is($user),
                );

                $this->assertEquals(
                    $guardName,
                    $event->guard,
                );

                return true;
            });
    }

    public function test_it_returns_two_factor_token_if_2fa_enabled(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->confirmed()
            ->for($user)
            ->create();

        $guardName = '::stateless_guard::';

        $tokenIssuerMock = $this->mock(AuthTokenIssuerContract::class);
        $dispatcherSpy = $this->spy(Dispatcher::class);

        $twoFactorResponseStub = TokenLoginResponse::pendingTwoFactorAuth($user, '::2fa_token::');

        $beforeLoginHookStub = new class($twoFactorResponseStub)
        {
            public function __construct(
                private readonly TokenLoginResponse $responseStub,
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

        $tokenIssuerMock->shouldReceive('useGuard')->andReturnSelf();

        // Act

        $actualResponse = resolve(StatelessLoginAction::class)
            ->execute(
                new LoginData($user, $guardName),
            );

        // Assert

        $this->assertSame($twoFactorResponseStub, $actualResponse);
        $this->assertTrue($actualResponse->getUser()->is($user));
        $this->assertTrue($actualResponse->twoFactor);

        $tokenIssuerMock->shouldNotHaveReceived('issue');
        $dispatcherSpy->shouldNotHaveReceived('dispatch');
    }
}
