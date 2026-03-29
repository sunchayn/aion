<?php

namespace Aion\Features\System;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\DeleteFolderOperation;
use Aion\Engine\Operations\RemoveComposerDependencyOperation;
use Aion\Engine\Operations\RemoveComposerScriptOperation;
use Aion\Engine\PathResolver;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

readonly class AgenticAiSystemFeature implements AionFeatureContract
{
    public static function getOptionSchema(): array
    {
        return [
            ConfigKeyEnum::AgenticAi->value => new OptionDefinition(
                label: 'Would you like to add Agentic AI support?',
                type: 'confirm',
                default: true,
                hint: 'Adds laravel boost package and extra AI Guidelines tailored to the shipped architecture'
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        if ($config->bool(ConfigKeyEnum::AgenticAi, true)) {
            return;
        }

        yield new DeleteFolderOperation('.ai');
        yield new RemoveComposerDependencyOperation('laravel/boost', dev: true);
        yield new RemoveComposerScriptOperation('post-update-cmd', '@php artisan boost:update --ansi');
    }
}
