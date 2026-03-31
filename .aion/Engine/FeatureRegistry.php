<?php

namespace Aion\Engine;

use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;

class FeatureRegistry
{
    /** @var array<int, AionFeatureContract> */
    private array $features = [];

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
     * @return AionFeatureContract[]
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
                return array_merge($carry, $feature->getOptionsDefinitions());
            },
            [],
        );
    }
}
