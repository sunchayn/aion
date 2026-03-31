<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class AddComposerDependencyOperation implements OperationContract
{
    use Concerns\InteractsWithComposer;

    public function __construct(
        private readonly string $packageName,
        private readonly string $version = '*',
        private readonly bool $dev = false
    ) {}

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot add dependency: 'composer.json' not found.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $this->updateComposer(
            $filesystem,
            action: 'add dependency',
            callback: function (array $composer) {
                $section = $this->dev ? 'require-dev' : 'require';

                $composer[$section][$this->packageName] = $this->version;

                ksort($composer[$section]);

                return $composer;
            },
        );
    }

    public function getDescription(): string
    {
        $type = $this->dev ? 'dev ' : '';

        return "Adding {$type}composer dependency '{$this->packageName}:{$this->version}'";
    }
}
