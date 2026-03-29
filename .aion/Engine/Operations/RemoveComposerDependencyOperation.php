<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RemoveComposerDependencyOperation implements OperationContract
{
    public function __construct(
        private readonly string $packageName,
        private readonly bool $dev = false
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        $composerPath = 'composer.json';

        if (! $filesystem->fileExists($composerPath)) {
            return;
        }

        $composer = json_decode($filesystem->read($composerPath), true);
        $section = $this->dev ? 'require-dev' : 'require';

        if (isset($composer[$section][$this->packageName])) {
            unset($composer[$section][$this->packageName]);
        }

        $filesystem->write(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot remove dependency: 'composer.json' not found.");
        }
    }

    public function getDescription(): string
    {
        $type = $this->dev ? 'dev ' : '';

        return "Removing {$type}composer dependency '{$this->packageName}'";
    }
}
