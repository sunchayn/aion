<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;

final readonly class ContextField implements FieldContract
{
    public function __construct(
        /** @var array<string, mixed> */
        private array $data,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'context' => $this->data,
        ];
    }
}
