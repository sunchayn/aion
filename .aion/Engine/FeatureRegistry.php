<?php

namespace Aion\Engine;

use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

/**
 * Central registry for Stacks and Features.
 */
/**
 * Central registry for Stacks and Features.
 */
class FeatureRegistry
{
    /** @var array<int, StackStrategyContract> */
    private array $stacks = [];

    /** @var array<int, AionFeatureContract> */
    private array $features = [];

    public function registerStack(string $stackClass): void
    {
        $this->stacks[] = new $stackClass;
    }

    public function registerStacks(array $stacks): void
    {
        foreach ($stacks as $stackClass) {
            $this->registerStack($stackClass);
        }
    }

    public function registerFeature(string $featureClass): void
    {
        $this->features[] = new $featureClass;
    }

    public function registerFeatures(array $featureClasses): void
    {
        foreach ($featureClasses as $featureClass) {
            $this->registerFeature($featureClass);
        }
    }

    /**
     * @return array<class-string<StackStrategyContract>>
     */
    public function getStacks(): array
    {
        return $this->stacks;
    }

    /**
     * @return array<class-string<AionFeatureContract>>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @return array<string, OptionDefinition>
     */
    public function getOptionDefinitions(): array
    {
        return array_reduce(
            $this->features,
            function (array $carry, AionFeatureContract $feature) {
                return array_merge($carry, $feature->getOptionSchema());
            },
            [],
        );
    }
}
