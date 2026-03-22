<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class AddComposerDependencyOperation implements OperationContract
{
    public function __construct(
        private readonly string $packageName,
        private readonly string $version = '*',
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

        $composer[$section][$this->packageName] = $this->version;

        // Sort packages
        ksort($composer[$section]);

        $filesystem->write(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot add dependency: 'composer.json' not found.");
        }
    }

    public function getDescription(): string
    {
        $type = $this->dev ? 'dev ' : '';

        return "Adding {$type}composer dependency '{$this->packageName}:{$this->version}'";
    }
}
