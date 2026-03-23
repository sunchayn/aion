<?php

namespace Aion\Engine;

use Aion\Engine\Operations\CopyFileOperation;
use Aion\Engine\Operations\DeleteFileOperation;
use Aion\Engine\Operations\DeleteFolderOperation;
use Aion\Engine\Operations\OperationContract;
use Aion\Engine\Operations\RunCommandOperation;
use Aion\Features\AionFeatureContract;
use Aion\Stacks\StackStrategyContract;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

class Engine
{
    private readonly FilesystemOperator $filesystem;

    public function __construct(
        private readonly FeatureRegistry $registry,
        private readonly StackStrategyContract $stack,
        private readonly AionConfig $configuration,
        private readonly PathResolver $pathResolver,
        private readonly bool $dryRun = false,
    ) {
        $adapter = new LocalFilesystemAdapter($this->pathResolver->root());
        $this->filesystem = new Filesystem($adapter);
    }

    public function bake(callable $onProgress): void
    {
        $operations = $this->resolveOperations();

        foreach ($operations as $operation) {
            $onProgress($operation->getDescription().($this->dryRun ? ' (Dry Run)' : ''));

            $operation->validate($this->filesystem);

            if (! $this->dryRun) {
                $operation->execute($this->filesystem);
            }
        }
    }

    /** @return OperationContract[] */
    private function resolveOperations(): array
    {
        return [
            ...$this->initializationOperations(),
            ...$this->stack->getOperations($this->pathResolver),
            ...$this->getFeatureOperations(),
            ...$this->cleanupOperations(),
        ];
    }

    /** @return iterable<OperationContract> */
    private function getFeatureOperations(): iterable
    {
        foreach ($this->registry->getFeatures() as $featureClass) {
            /** @var AionFeatureContract $feature */
            $feature = new $featureClass;
            yield from $feature->getOperations($this->stack, $this->configuration, $this->pathResolver);
        }
    }

    /** @return OperationContract[] */
    private function initializationOperations(): array
    {
        return [
            new CopyFileOperation(
                source: $this->pathResolver->stub('composer.json'),
                destination: 'composer.json',
            ),
            new DeleteFileOperation(filePath: 'composer.lock'),
        ];
    }

    /** @return OperationContract[] */
    private function cleanupOperations(): array
    {
        return [
            new RunCommandOperation('./vendor/bin/pint --silent'),
            new DeleteFolderOperation($this->pathResolver->internal()),
            new DeleteFileOperation('.github/workflows/aion-tests.yml'),
        ];
    }
}
