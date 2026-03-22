<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS;

use App\Infrastructure\Logging\ECS\EcsFormatter;
use App\Infrastructure\Logging\ECS\Fields\ContextField;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(EcsFormatter::class)]
#[Group('logging')]
class EcsFormatterUnitTest extends UnitTestCase
{
    public function test_it_formats_log_record_to_ecs_json(): void
    {
        // Arrange

        $formatter = new EcsFormatter;

        $formatter->setContextBuilder(fn (array $context): array => array_merge(['shared' => 'context'], $context));

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2024-01-01T12:00:00.000000Z'),
            channel: 'test-channel',
            level: Level::Info,
            message: 'Test message',
            context: ['local' => 'context_value'],
            extra: ['label1' => 'value1']
        );

        // Act

        $result = $formatter->format($record);

        // Assert

        $decoded = json_decode($result, true);

        $this->assertEquals(
            [
                '@timestamp' => '2024-01-01T12:00:00.000000+00:00',
                'ecs' => [
                    'version' => '8.11.0',
                ],
                'log' => [
                    'level' => 'info',
                    'logger' => 'test-channel',
                ],
                'message' => 'Test message',
                'context' => [
                    'shared' => 'context',
                    'local' => 'context_value',
                ],
                'labels' => [
                    'label1' => 'value1',
                ],
            ],
            $decoded,
        );
    }

    public function test_it_throws_exception_if_context_builder_not_set(): void
    {
        // Arrange

        $formatter = new EcsFormatter;
        $record = new LogRecord(
            datetime: new DateTimeImmutable('2024-01-01T12:00:00.000000Z'),
            channel: 'test-channel',
            level: Level::Info,
            message: 'Test message',
        );

        $this->expectException(RuntimeException::class);

        // Act

        $formatter->format($record);
    }

    public function test_it_handles_empty_context(): void
    {
        // Arrange

        $formatter = new EcsFormatter;

        $formatter->setContextBuilder(fn (array $context): array => []);

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2024-01-01T12:00:00.000000Z'),
            channel: 'test-channel',
            level: Level::Info,
            message: 'Test message',
            context: []
        );

        // Act

        $result = $formatter->format($record);

        // Assert

        $decoded = json_decode($result, true);

        $this->assertEquals(
            [
                '@timestamp' => '2024-01-01T12:00:00.000000+00:00',
                'ecs' => [
                    'version' => '8.11.0',
                ],
                'log' => [
                    'level' => 'info',
                    'logger' => 'test-channel',
                ],
                'message' => 'Test message',
            ],
            $decoded,
        );
    }

    public function test_it_handles_field_contract_and_exception_in_context(): void
    {
        // Arrange

        $formatter = new EcsFormatter;

        $formatter->setContextBuilder(fn (array $context): array => $context);

        $exception = new RuntimeException('Test error');
        $customField = new ContextField(['custom' => 'data']);

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2024-01-01T12:00:00.000000Z'),
            channel: 'test-channel',
            level: Level::Info,
            message: 'Test message',
            context: [
                'field' => $customField,
                'exception' => $exception,
            ]
        );

        // Act

        $result = $formatter->format($record);

        // Assert

        $decoded = json_decode($result, true);

        $this->assertEquals(
            [
                '@timestamp' => '2024-01-01T12:00:00.000000+00:00',
                'ecs' => [
                    'version' => '8.11.0',
                ],
                'log' => [
                    'level' => 'info',
                    'logger' => 'test-channel',
                ],
                'message' => 'Test message',
                'error' => [
                    'code' => '0',
                    'id' => RuntimeException::class,
                    'message' => 'Test error',
                    'stacktrace' => $decoded['error']['stacktrace'],
                    'type' => RuntimeException::class,
                ],
                'context' => [
                    'custom' => 'data',
                ],
            ],
            $decoded,
        );
    }
}
