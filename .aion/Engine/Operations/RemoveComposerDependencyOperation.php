<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RemoveComposerDependencyOperation implements OperationContract
{
    use Concerns\InteractsWithComposer;

    public function __construct(
        private readonly string $packageName,
        private readonly bool $dev = false
    ) {}

    public function getDescription(): string
    {
        $type = $this->dev ? 'dev ' : '';

        return "Removing {$type}composer dependency '{$this->packageName}'";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot remove dependency: 'composer.json' not found.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $this->updateComposer($filesystem, 'remove dependency', function (array $composer) {
            $section = $this->dev ? 'require-dev' : 'require';

            unset($composer[$section][$this->packageName]);

            return $composer;
        });
    }
}
