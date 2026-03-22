<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;
use App\Infrastructure\Logging\ECS\Fields\ContextField;
use App\Infrastructure\Logging\ECS\Fields\ErrorField;
use Closure;
use Illuminate\Support\Collection;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use RuntimeException;
use Throwable;

final class EcsFormatter extends NormalizerFormatter
{
    // @see https://www.elastic.co/guide/en/ecs/8.11/index.html
    private const string ECS_VERSION = '8.11.0';

    private ?Closure $contextBuilder = null;

    public function setContextBuilder(Closure $contextBuilder): void
    {
        $this->contextBuilder = $contextBuilder;
    }

    public function format(LogRecord $record): string
    {
        throw_if(! $this->contextBuilder instanceof Closure, RuntimeException::class, 'The <'.EcsLogger::class.'> MUST pass the context builder.');

        $rawContext = ($this->contextBuilder)($record->context);

        $context = $this
            ->contextToEcsFields($rawContext)
            ->flatMap(fn (FieldContract $field): array => $field->toArray())
            ->all();

        $ecsLog = [
            '@timestamp' => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'ecs' => [
                'version' => self::ECS_VERSION,
            ],
            'log' => [
                'level' => strtolower($record->level->getName()),
                'logger' => $record->channel,
            ],
            'message' => $record->message,
            ...$context,
        ];

        // Add extra fields if present
        if ($record->extra !== []) {
            $ecsLog['labels'] = $record->extra;
        }

        return $this->toJson($ecsLog)."\n";
    }

    /**
     * @param  array<string, mixed>  $context
     * @return Collection<array-key, FieldContract>
     */
    private function contextToEcsFields(array $context): Collection
    {
        if ($context === []) {
            return Collection::empty();
        }

        $newContext = [];
        $rawProperties = [];

        foreach ($context as $key => $value) {
            if ($value instanceof FieldContract) {
                $newContext[] = $value;

                continue;
            }

            if ($key === 'exception' && $value instanceof Throwable) {
                // Convert exceptions added to the context (from the Exception Handler) to proper ECS field.
                $newContext[] = ErrorField::fromThrowable($value);

                continue;
            }

            $rawProperties[$key] = $value;
        }

        if ($rawProperties !== []) {
            $newContext[] = new ContextField((array) $this->normalizeValue($rawProperties));
        }

        return collect($newContext);
    }
}
