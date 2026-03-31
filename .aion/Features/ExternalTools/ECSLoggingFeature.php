<?php

namespace Aion\Features\ExternalTools;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Choices\Enums\LoggingInfrastructureEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\CopyFileOperation;
use Aion\Engine\Operations\DeleteFileOperation;
use Aion\Engine\Operations\DeleteFolderOperation;
use Aion\Engine\Operations\ReplaceTextOperation;
use Aion\Engine\PathResolver;
use Aion\Engine\PromptTypeEnum;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;

readonly class ECSLoggingFeature implements AionFeatureContract
{
    public static function getOptionsDefinitions(): array
    {
        return [
            ConfigKeyEnum::LoggingStructure->value => new OptionDefinition(
                label: 'Do you want to use standardized logging structure (ECS)?',
                type: PromptTypeEnum::Select,
                default: LoggingInfrastructureEnum::Laravel->value,
                options: LoggingInfrastructureEnum::toOptions(),
                hint: 'Read more: https://www.elastic.co/docs/reference/ecs/ecs-log',
                transformer: fn ($value) => LoggingInfrastructureEnum::from($value),
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        if ($config->get(ConfigKeyEnum::LoggingStructure) === LoggingInfrastructureEnum::ECS) {
            yield from $this->getEcsOperations($pathResolver);

            return;
        }

        yield from $this->getCleanupOperations();
    }

    private function getEcsOperations(PathResolver $pathResolver): iterable
    {
        yield new CopyFileOperation(
            source: $pathResolver->stub('ecs/config/logging.php'),
            destination: 'config/logging.php',
        );

        yield new ReplaceTextOperation(
            filePath: 'bootstrap/app.php',
            search: 'InitiateSharedLoggingContextMiddleware',
            replace: 'InitiateSharedEcsLoggingContextMiddleware',
        );
    }

    private function getCleanupOperations(): iterable
    {
        yield new DeleteFolderOperation('app/Infrastructure/Logging/ECS');

        yield new DeleteFileOperation('app/Infrastructure/Http/Middleware/InitiateSharedEcsLoggingContextMiddleware.php');

        yield new DeleteFileOperation('tests/App/Infrastructure/Http/Middleware/InitiateSharedEcsLoggingContextMiddlewareUnitTest.php');

        yield new DeleteFolderOperation('tests/App/Infrastructure/Logging/ECS');
    }
}
