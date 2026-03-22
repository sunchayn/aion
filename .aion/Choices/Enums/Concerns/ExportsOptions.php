<?php

namespace Aion\Choices\Enums\Concerns;

trait ExportsOptions
{
    public static function toOptions(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases(),
        );
    }
}
