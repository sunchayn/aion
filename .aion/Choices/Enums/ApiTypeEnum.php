<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum ApiTypeEnum: string
{
    use ExportsOptions;

    case Stateless = 'stateless';

    case Stateful = 'stateful';

    public function label(): string
    {
        return match ($this) {
            self::Stateless => 'Stateless (Token-based)',
            self::Stateful => 'Stateful (Session-based)',
        };
    }
}
