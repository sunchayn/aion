<?php

namespace Tests\App\Infrastructure\Http\Middleware;

use App\Infrastructure\Http\Middleware\InitiateSharedLoggingContextMiddleware;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(InitiateSharedLoggingContextMiddleware::class)]
#[Group('middleware')]
class InitiateSharedLoggingContextMiddlewareFunctionalTest extends FunctionalTestCase
{
    public function test_it_shares_logging_context_with_logger(): void
    {
        // Arrange

        $user = User::factory()->create();

        auth()->login($user);

        $request = Request::create('/ping', 'POST');

        $request->headers->add([
            'x-request-id' => $requestId = fake()->uuid(),
        ]);

        $middleware = resolve(InitiateSharedLoggingContextMiddleware::class);

        $nextCallback = (fn (Request $request) => response()->json([
            'data' => 'Ok!',
        ]));

        // Act

        $result = $middleware->handle($request, $nextCallback);

        // Assert

        $this->assertEquals(json_encode(['data' => 'Ok!']), $result->content());

        $this->assertSharedContext($user, $requestId, $request);
    }

    public function test_it_generates_request_id_in_the_spot_when_missing(): void
    {
        // Arrange

        $user = User::factory()->create();

        auth()->login($user);

        $request = Request::create('/ping', 'POST');

        $requestId = fake()->uuid();
        Str::createUuidsUsing(fn (): UuidInterface => Uuid::fromString($requestId));

        $middleware = resolve(InitiateSharedLoggingContextMiddleware::class);

        $nextCallback = (fn (Request $request) => response()->json([
            'data' => 'Ok!',
        ]));

        // Act

        $result = $middleware->handle($request, $nextCallback);

        // Assert

        $this->assertEquals(json_encode(['data' => 'Ok!']), $result->content());

        $this->assertSharedContext($user, $requestId, $request);
    }

    /*
     * Asserts.
     */

    public function assertSharedContext(Model|Collection $user, string $requestId, Request $request): void
    {
        Log::assertHasSharedContext(
            function (array $sharedContext) use ($user, $requestId, $request): true {
                $this->assertEquals(
                    [
                        'user' => [
                            'id' => $user->id,
                        ],
                        'http' => [
                            'request' => [
                                'id' => $requestId,
                                'method' => 'POST',
                            ],
                        ],
                        'url' => [
                            'full' => 'http://localhost/ping',
                            'path' => 'ping',
                            'scheme' => 'http',
                            'domain' => 'localhost',
                            'port' => 80,
                            'query' => null,
                        ],
                        'user_agent' => [
                            'original' => 'Symfony',
                        ],
                        'client' => [
                            'ip' => $request->getClientIp(),
                        ],
                    ],
                    $sharedContext,
                );

                return true;
            }
        );
    }
}
