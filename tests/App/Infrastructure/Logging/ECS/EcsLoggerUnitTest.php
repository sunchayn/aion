<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS;

use App\Infrastructure\Logging\ECS\EcsFormatter;
use App\Infrastructure\Logging\ECS\EcsLogger;
use Illuminate\Log\LogManager;
use Mockery;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Level;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(EcsLogger::class)]
#[Group('logging')]
class EcsLoggerUnitTest extends UnitTestCase
{
    public function test_it_creates_logger_with_ecs_formatter_and_stream_handler(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = ['path' => 'php://stderr', 'level' => Level::Debug];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        $this->assertEquals('ecs', $logger->getName());

        $handlers = $logger->getHandlers();

        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);

        /** @var StreamHandler $handler */
        $handler = $handlers[0];

        $this->assertInstanceOf(EcsFormatter::class, $handler->getFormatter());
        $this->assertEquals(Level::Debug, $handler->getLevel());
    }

    public function test_it_creates_logger_with_default_config(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        // Act

        $logger = $ecsLogger([]);

        // Assert

        $handlers = $logger->getHandlers();

        $this->assertCount(1, $handlers);
    }

    public function test_it_merges_shared_context_via_formatter(): void
    {
        // Arrange

        $logManagerMock = Mockery::mock(LogManager::class);
        $ecsLogger = new EcsLogger($logManagerMock);

        // Capture output using a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'ecs_log');
        $logger = $ecsLogger(['path' => $tempFile, 'level' => Level::Info]);

        // Anticipate

        $logManagerMock->shouldReceive('sharedContext')->once()->andReturn(['shared_key' => 'shared_value']);

        // Act

        $logger->info('Test message', ['local_key' => 'local_value']);

        // Assert

        $output = file_get_contents($tempFile);

        $decoded = json_decode($output, true);

        $this->assertEquals(
            [
                'shared_key' => 'shared_value',
                'local_key' => 'local_value',
            ],
            $decoded['context'],
        );

        $this->assertEquals('info', $decoded['log']['level']);
        $this->assertEquals('Test message', $decoded['message']);
        $this->assertEquals('8.11.0', $decoded['ecs']['version']);
    }

    public function test_it_respects_custom_config(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = [
            'path' => 'php://memory',
            'level' => Level::Critical,
        ];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        /** @var StreamHandler $handler */
        $handler = $logger->getHandlers()[0];

        $this->assertEquals(Level::Critical, $handler->getLevel());
    }

    public function test_it_creates_logger_with_slack_handler(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = ['slack_url' => 'https://hooks.slack.com/services/xxx', 'level' => Level::Error];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        $handlers = $logger->getHandlers();

        $this->assertInstanceOf(SlackWebhookHandler::class, $handlers[0]);
        $this->assertEquals(Level::Error, $handlers[0]->getLevel());
    }

    public function test_it_creates_logger_with_rotating_handler(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = ['path' => 'foo.log', 'days' => 7, 'level' => Level::Info];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        $handlers = $logger->getHandlers();

        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
        $this->assertEquals(Level::Info, $handlers[0]->getLevel());
    }

    public function test_it_adds_processors_to_logger(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = [
            'processors' => [
                PsrLogMessageProcessor::class,
                fn ($record) => $record,
            ],
        ];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        $this->assertCount(2, $logger->getProcessors());
    }

    public function test_it_creates_logger_with_custom_handler(): void
    {
        // Arrange

        $ecsLogger = new EcsLogger(Mockery::mock(LogManager::class));

        $config = [
            'handler' => SyslogHandler::class,
            'handler_with' => [
                'ident' => 'my-app',
            ],
            'level' => Level::Notice,
        ];

        // Act

        $logger = $ecsLogger($config);

        // Assert

        $handlers = $logger->getHandlers();

        $this->assertInstanceOf(SyslogHandler::class, $handlers[0]);

        $this->assertEquals(Level::Notice, $handlers[0]->getLevel());
    }
}
