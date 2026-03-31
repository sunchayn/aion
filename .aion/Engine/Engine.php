<?php

namespace Aion\Engine;

use Aion\Engine\Operations\CopyFileOperation;
use Aion\Engine\Operations\DeleteFileOperation;
use Aion\Engine\Operations\DeleteFolderOperation;
use Aion\Engine\Operations\OperationContract;
use Aion\Engine\Operations\RunCommandOperation;
use Aion\Stacks\StackStrategyContract;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

class Engine
{
    public function __construct(
        private readonly FeatureRegistry $featureRegistry,
        private readonly StackStrategyContract $stack,
        private readonly AionConfig $configuration,
        private readonly PathResolver $pathResolver,
        private readonly bool $dryRun = false,
        private ?FilesystemOperator $filesystem = null,
    ) {
        $this->filesystem ??= new Filesystem(new LocalFilesystemAdapter($this->pathResolver->root()));
    }

    public function yieldBakingOperations(): iterable
    {
        $operations = $this->resolveOperations();

        foreach ($operations as $operation) {
            yield $operation->getDescription().($this->dryRun ? ' (Dry Run)' : '');

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

    /** @return iterable<OperationContract> */
    private function getFeatureOperations(): iterable
    {
        foreach ($this->featureRegistry->getFeatures() as $feature) {
            yield from $feature->getOperations($this->stack, $this->configuration, $this->pathResolver);
        }
    }

    /** @return OperationContract[] */
    private function cleanupOperations(): array
    {
        $cleanup = [
            new RunCommandOperation('composer exec pint', quiet: true),
            new DeleteFileOperation('.github/workflows/aion-tests.yml'),
        ];

        // On Windows, the .aion folder cannot be deleted while the spark command is running from it.
        if (PHP_OS_FAMILY !== 'Windows') {
            $cleanup[] = new DeleteFolderOperation($this->pathResolver->internal());
        }

        return $cleanup;
    }
}
