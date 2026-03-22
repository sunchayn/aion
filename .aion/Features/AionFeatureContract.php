<?php

namespace Aion\Features;

use Aion\Engine\AionConfig;
use Aion\Engine\Operations\OperationContract;
use Aion\Engine\PathResolver;
use Aion\Stacks\StackStrategyContract;

/**
 * Contract for all Aion Features.
 */
interface AionFeatureContract
{
    /**
     * Get the configuration schema for this feature.
     *
     * @return array<string, OptionDefinition>
     */
    public static function getOptionSchema(): array;

    /**
     * Get the operations (setup/cleanup) for this feature based on configuration.
     *
     * @return iterable<OperationContract>
     */
    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable;
}
