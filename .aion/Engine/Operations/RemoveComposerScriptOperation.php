<?php

namespace Aion\Engine\Operations;

use Aion\Engine\Operations\Concerns\InteractsWithComposer;
use Illuminate\Support\Collection;
use League\Flysystem\FilesystemOperator;

class RemoveComposerScriptOperation implements OperationContract
{
    use InteractsWithComposer;

    public function __construct(
        private readonly string $event,
        private readonly string $command
    ) {}

    public function getDescription(): string
    {
        return "Removing composer script '{$this->command}' from '{$this->event}'";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot remove script: 'composer.json' not found.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $this->updateComposer($filesystem, 'remove script', function (array $composer) {
            if (! isset($composer['scripts'][$this->event])) {
                return $composer;
            }

            $composer['scripts'][$this->event] = Collection::make((array) $composer['scripts'][$this->event])
                ->reject(fn (string $cmd) => $cmd === $this->command)
                ->values()
                ->whenEmpty(fn () => null)
                ->pipe(fn ($scripts) => $scripts?->count() === 1 ? $scripts->first() : $scripts?->all());

            if ($composer['scripts'][$this->event] === null) {
                unset($composer['scripts'][$this->event]);
            }

            return $composer;
        });
    }
}
