<?php

namespace Aion\Features\System;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Choices\Enums\LogChannelEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\RemoveConfigBlockOperation;
use Aion\Engine\PathResolver;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

readonly class LogSystemFeature implements AionFeatureContract
{
    public static function getOptionSchema(): array
    {
        return [
            ConfigKeyEnum::Logs->value => new OptionDefinition(
                label: 'Select needed Log channels',
                type: 'multiselect',
                default: [LogChannelEnum::Daily->value, LogChannelEnum::Stderr->value],
                options: LogChannelEnum::toOptions(),
                transformer: fn ($value) => is_array($value)
                    ? array_map(fn ($value) => LogChannelEnum::from($value), $value)
                    : [LogChannelEnum::from($value)]
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        $selected = $config->array(ConfigKeyEnum::Logs);
        $selectedValues = array_map(fn (LogChannelEnum $logChannel) => $logChannel->value, $selected);

        $allChannels = array_map(fn (LogChannelEnum $logChannel) => $logChannel->value, LogChannelEnum::cases());

        foreach ($allChannels as $configKey) {
            if (! in_array($configKey, $selectedValues)) {
                yield new RemoveConfigBlockOperation('config/logging.php', $configKey);
            }
        }
    }
}
