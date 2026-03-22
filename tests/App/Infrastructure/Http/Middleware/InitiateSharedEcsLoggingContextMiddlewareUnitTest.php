<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Http\Middleware;

use App\Infrastructure\Http\Middleware\InitiateSharedEcsLoggingContextMiddleware;
use App\Infrastructure\Logging\ECS\Fields\HttpRequestsFieldsAggregator;
use App\Infrastructure\Logging\ECS\Fields\UserField;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(InitiateSharedEcsLoggingContextMiddleware::class)]
#[Group('logging')]
class InitiateSharedEcsLoggingContextMiddlewareUnitTest extends UnitTestCase
{
    #[TestWith([true])]
    #[TestWith([false])]
    public function test_it_shares_ecs_fields_to_logger(bool $isLoggedIn): void
    {
        // Arrange

        $request = Request::create('/test');
        $request->headers->set('x-request-id', fake()->uuid());

        $response = new Response;

        $user = $isLoggedIn
            ? new User()->forceFill(['id' => fake()->randomNumber()])
            : null;

        // Anticipate

        $logManagerSpy = Mockery::spy(LogManager::class);

        $authManagerMock = Mockery::mock(
            AuthManager::class,
            function (MockInterface $mock) use ($user): void {
                $mock->shouldReceive('user')->once()->andReturn($user);
            },
        );

        $middleware = new InitiateSharedEcsLoggingContextMiddleware(
            logger: $logManagerSpy,
            authManager: $authManagerMock,
        );

        // Act

        $actual = $middleware->handle($request, fn (): Response => $response);

        // Assert

        $this->assertSame($response, $actual);

        $logManagerSpy
            ->shouldHaveReceived('shareContext')
            ->withArgs(function (array $contextArg) use ($request, $user): bool {
                $this->assertCount(2, $contextArg);

                $this->assertInstanceOf(HttpRequestsFieldsAggregator::class, $contextArg[0]);

                $this->assertInstanceOf(UserField::class, $contextArg[1]);

                $this->assertEquals(
                    new HttpRequestsFieldsAggregator($request)->toArray(),
                    $contextArg[0]->toArray()
                );

                $this->assertEquals(
                    new UserField($user)->toArray(),
                    $contextArg[1]->toArray()
                );

                return true;
            });
    }
}
