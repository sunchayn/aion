<?php

namespace Aion\Features\System;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Choices\Enums\DatabaseConnectionEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\RemoveConfigBlockOperation;
use Aion\Engine\PathResolver;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

readonly class DatabaseSystemFeature implements AionFeatureContract
{
    public static function getOptionSchema(): array
    {
        return [
            ConfigKeyEnum::DB->value => new OptionDefinition(
                label: 'Select needed DB connections',
                type: 'multiselect',
                default: [DatabaseConnectionEnum::MySQL->value, DatabaseConnectionEnum::SQLite->value],
                options: DatabaseConnectionEnum::toOptions(),
                transformer: fn ($value) => is_array($value)
                    ? array_map(fn ($v) => DatabaseConnectionEnum::from($v), $value)
                    : [DatabaseConnectionEnum::from($value)]
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        $selected = $config->array(ConfigKeyEnum::DB);
        $selectedValues = array_map(fn (DatabaseConnectionEnum $dbConnection) => $dbConnection->value, $selected);

        $allConnections = array_map(fn (DatabaseConnectionEnum $dbConnection) => $dbConnection->value, DatabaseConnectionEnum::cases());

        foreach ($allConnections as $connection) {
            if (! in_array($connection, $selectedValues)) {
                yield new RemoveConfigBlockOperation('config/database.php', $connection);
            }
        }
    }
}
