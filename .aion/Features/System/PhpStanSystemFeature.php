<?php

namespace Aion\Features\System;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Choices\Enums\PhpStanLevelEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\ReplaceTextOperation;
use Aion\Engine\PathResolver;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

readonly class PhpStanSystemFeature implements AionFeatureContract
{
    public static function getOptionSchema(): array
    {
        return [
            ConfigKeyEnum::PhpStan->value => new OptionDefinition(
                label: 'Select PHPStan strictness level',
                type: 'select',
                default: PhpStanLevelEnum::Level8->value,
                options: PhpStanLevelEnum::toOptions(),
                transformer: fn ($value) => PhpStanLevelEnum::from($value)
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        $level = $config->get(ConfigKeyEnum::PhpStan);

        yield new ReplaceTextOperation(
            filePath: 'tools/phpstan/phpstan.neon.dist',
            search: 'level: 8',
            replace: "level: {$level->value}"
        );
    }
}
