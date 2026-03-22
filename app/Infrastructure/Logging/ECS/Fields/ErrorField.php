<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;
use Throwable;

/**
 * ECS Event Field.
 *
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-error.html
 */
final readonly class ErrorField implements FieldContract
{
    public function __construct(
        private string $code,
        private string $id,
        private string $message,
        /** @var array<int, mixed> */
        private array $stackTrace,
        private string $type,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error' => [
                'code' => $this->code,
                'id' => $this->id,
                'message' => $this->message,
                'stacktrace' => $this->stackTrace,
                'type' => $this->type,
            ],
        ];
    }

    public static function fromThrowable(Throwable $throwable): self
    {
        return new self(
            code: (string) $throwable->getCode(),
            id: $throwable::class,
            message: $throwable->getMessage(),
            stackTrace: $throwable->getTrace(),
            type: $throwable::class,
        );
    }
}
