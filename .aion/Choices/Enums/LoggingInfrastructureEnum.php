<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum LoggingInfrastructureEnum: string
{
    use ExportsOptions;

    case ECS = 'ecs';

    case Laravel = 'default';

    public function label(): string
    {
        return match ($this) {
            self::ECS => 'Elastic Common Schema (ECS)',
            self::Laravel => 'Default',
        };
    }
}
