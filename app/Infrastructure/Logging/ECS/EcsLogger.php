<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS;

use Illuminate\Log\LogManager;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final readonly class EcsLogger
{
    public function __construct(
        private LogManager $logManager,
    ) {}

    /** @param array<string, mixed> $config
     * @throws MissingExtensionException
     */
    public function __invoke(array $config): LoggerInterface
    {
        $logger = new Logger($config['name'] ?? 'ecs');

        // Set ECS formatter with context builder
        $formatter = new EcsFormatter;
        $formatter->setContextBuilder(fn (array $context): array => $this->buildContext($context));

        // Configure handler
        $handler = $this->createHandler($config)->setFormatter($formatter);
        $logger->pushHandler($handler);

        $this->pushProcessors($config, $logger);

        return $logger;
    }

    /** @param array<string, mixed> $config
     * @throws MissingExtensionException
     */
    private function createHandler(array $config): HandlerInterface
    {
        $level = $config['level'] ?? Level::Debug;

        // Custom Handler
        if (isset($config['handler'])) {
            $handlerClass = $config['handler'];
            $with = $config['handler_with'] ?? [];

            return new $handlerClass(...$with, level: $level);
        }

        // Slack
        if (isset($config['slack_url'])) {
            return new SlackWebhookHandler(
                webhookUrl: $config['slack_url'],
                channel: $config['channel'] ?? null,
                username: $config['username'] ?? 'Laravel',
                useAttachment: $config['use_attachment'] ?? true,
                iconEmoji: $config['emoji'] ?? ':boom:',
                level: $level
            );
        }

        // Daily (Rotating)
        if (isset($config['days'])) {
            return new RotatingFileHandler(
                filename: $config['path'] ?? storage_path('logs/laravel.log'),
                maxFiles: (int) $config['days'],
                level: $level
            );
        }

        // Default Stream (Single, Stderr, etc.)
        return new StreamHandler(
            stream: $config['path'] ?? 'php://stderr',
            level: $level
        );
    }

    /**
     * @param  array<string, mixed>  $providedContext
     * @return array<string, mixed>
     */
    private function buildContext(array $providedContext): array
    {
        return array_merge(
            $this->logManager->sharedContext(),
            $providedContext,
        );
    }

    private function pushProcessors(array $config, Logger $logger): void
    {
        if (! isset($config['processors'])) {
            return;
        }

        if (! is_array($config['processors'])) {
            return;
        }

        foreach ($config['processors'] as $processor) {
            $logger->pushProcessor(
                is_callable($processor)
                    ? $processor
                    : new $processor,
            );
        }
    }
}
