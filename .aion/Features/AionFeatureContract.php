<?php

namespace Aion\Features;

use Aion\Engine\AionConfig;
use Aion\Engine\Operations\OperationContract;
use Aion\Engine\PathResolver;
use Aion\Stacks\StackStrategyContract;

interface AionFeatureContract
{
    /**
     * Get the configuration schema for this feature.
     *
     * @return array<string, OptionDefinition>
     */
    public static function getOptionsDefinitions(): array;

    /**
     * Get the operations (setup/cleanup) for this feature based on configuration.
     *
     * @return iterable<OperationContract>
     */
    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable;
}
