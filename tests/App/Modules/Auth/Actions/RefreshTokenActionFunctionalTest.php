<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\RefreshTokenAction;
use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Auth\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RefreshTokenAction::class)]
#[CoversClass(InvalidRefreshTokenException::class)]
#[Group('authentication')]
class RefreshTokenActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_returns_new_logged_in_user_on_valid_refresh_token(): void
    {
        // Arrange

        Event::fake();

        $user = User::factory()->create();

        $this->mock(
            AuthTokenIssuerContract::class,
            function (MockInterface $mock) use ($user): void {
                $mock->expects('useGuard')
                    ->withAnyArgs()
                    ->andReturnSelf();

                $mock->expects('refresh')
                    ->with('old-token')
                    ->andReturn(new IssuedTokens('new-token', 'new-refresh-token'));

                $mock->expects('userFromToken')
                    ->with('new-token')
                    ->andReturn($user);

            }
        );

        // Act

        $loggedInUser = resolve(RefreshTokenAction::class)->execute('old-token');

        // Assert

        $data = $loggedInUser->toResponse(request())->getData(true)['data'];

        $this->assertEquals('new-token', $data['auth_token']);

        $this->assertEquals('new-refresh-token', $data['refresh_token']);

        Event::assertDispatched(Login::class);
    }

    public function test_it_throws_exception_when_token_issuer_returns_null(): void
    {
        // Arrange

        $this->mock(
            AuthTokenIssuerContract::class,
            function (MockInterface $mock): void {
                $mock->expects('useGuard')
                    ->withAnyArgs()
                    ->andReturnSelf();

                $mock->expects('refresh')
                    ->with('bad-token')
                    ->andReturnNull();
            }
        );

        // Anticipate

        $this->expectException(InvalidRefreshTokenException::class);

        // Act

        resolve(RefreshTokenAction::class)->execute('bad-token');
    }

    public function test_it_throws_exception_when_user_from_token_is_invalid(): void
    {
        // Arrange

        $this->mock(
            AuthTokenIssuerContract::class,
            function (MockInterface $mock): void {
                $mock->expects('useGuard')
                    ->withAnyArgs()
                    ->andReturnSelf();

                $mock->expects('refresh')
                    ->with('good-token')
                    ->andReturn(new IssuedTokens('new-token', 'new-refresh-token'));

                $mock->expects('userFromToken')
                    ->with('new-token')
                    ->andReturnNull(); // Invalid user
            }
        );

        // Anticipate

        $this->expectException(InvalidRefreshTokenException::class);

        // Act

        resolve(RefreshTokenAction::class)->execute('good-token');
    }
}
