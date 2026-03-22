<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;

/**
 * ECS Event Field.
 *
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-event.html
 */
final readonly class EventField implements FieldContract
{
    public function __construct(
        private string $action,
        private string $category,
        private string $outcome,
        private ?string $reason = null,
        private ?string $type = null,
        /** @var array<string, mixed> */
        private array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $event = [
            'action' => $this->action,
            'category' => [$this->category],
            'outcome' => $this->outcome,
        ];

        if ($this->reason !== null) {
            $event['reason'] = $this->reason;
        }

        if ($this->type !== null) {
            $event['type'] = [$this->type];
        }

        // Merge any additional metadata
        if ($this->metadata !== []) {
            $event = array_merge($event, $this->metadata);
        }

        return [
            'event' => $event,
        ];
    }
}
