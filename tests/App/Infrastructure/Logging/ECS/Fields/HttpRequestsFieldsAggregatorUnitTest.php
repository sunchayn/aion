<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Fields\HttpRequestsFieldsAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Ramsey\Uuid\Uuid;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(HttpRequestsFieldsAggregator::class)]
#[Group('logging')]
class HttpRequestsFieldsAggregatorUnitTest extends UnitTestCase
{
    public function test_it_formats_http_request_fields_with_header_id(): void
    {
        // Arrange

        $request = Request::create(
            '/api/test?foo=bar',
            'POST',
            server: [
                'HTTP_HOST' => 'example.com',
                'HTTP_USER_AGENT' => 'TestAgent/1.0',
                'REMOTE_ADDR' => '192.168.1.1',
            ],
            content: 'test-body-content',
        );

        $request->headers->set('x-request-id', 'req-12345');

        $aggregator = new HttpRequestsFieldsAggregator($request);

        // Act

        $actual = $aggregator->toArray();

        // Assert

        $this->assertEquals(
            [
                'http' => [
                    'request' => [
                        'id' => 'req-12345',
                        'method' => 'POST',
                        'body' => [
                            'bytes' => 17,
                        ],
                    ],
                ],
                'url' => [
                    'full' => 'http://example.com/api/test?foo=bar',
                    'path' => 'api/test',
                    'scheme' => 'http',
                    'domain' => 'example.com',
                    'port' => 80,
                    'query' => 'foo=bar',
                ],
                'user_agent' => [
                    'original' => 'TestAgent/1.0',
                ],
                'client' => [
                    'ip' => '192.168.1.1',
                ],
            ],
            $actual,
        );
    }

    public function test_it_formats_http_request_fields_generates_id_when_missing(): void
    {
        // Arrange

        $request = Request::create('/');

        $uuid = fake()->uuid();

        Str::createUuidsUsing(fn () => Uuid::fromString($uuid));

        $aggregator = new HttpRequestsFieldsAggregator($request);

        // Act

        $actual = $aggregator->toArray();

        // Assert

        $this->assertEquals(
            [
                'http' => [
                    'request' => [
                        'id' => $uuid,
                        'method' => 'GET',
                        'body' => [
                            'bytes' => 0,
                        ],
                    ],
                ],
                'url' => [
                    'full' => 'http://localhost',
                    'path' => '/',
                    'scheme' => 'http',
                    'domain' => 'localhost',
                    'port' => 80,
                    'query' => null,
                ],
                'user_agent' => [
                    'original' => 'Symfony',
                ],
                'client' => [
                    'ip' => '127.0.0.1',
                ],
            ],
            $actual,
        );
    }
}
