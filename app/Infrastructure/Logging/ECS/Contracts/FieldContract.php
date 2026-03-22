<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Contracts;

/**
 * @see https://www.elastic.co/docs/reference/ecs/ecs-field-reference
 */
interface FieldContract
{
    /**
     * Convert the field to an array representation for ECS logging.
     *
     * @return array<array-key, array<string, mixed>>
     */
    public function toArray(): array;
}
