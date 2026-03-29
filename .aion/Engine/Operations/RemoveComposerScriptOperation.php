<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RemoveComposerScriptOperation implements OperationContract
{
    public function __construct(
        private readonly string $event,
        private readonly string $command
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        $composerPath = 'composer.json';

        if (! $filesystem->fileExists($composerPath)) {
            return;
        }

        $composer = json_decode($filesystem->read($composerPath), true);

        if (! isset($composer['scripts'][$this->event])) {
            return;
        }

        // If it's an array, remove the specific command
        if (is_array($composer['scripts'][$this->event])) {
            $composer['scripts'][$this->event] = array_values(
                array_filter(
                    $composer['scripts'][$this->event],
                    fn (string $cmd) => $cmd !== $this->command
                ),
            );

            // If the array becomes empty, remove the event key entirely
            if (empty($composer['scripts'][$this->event])) {
                unset($composer['scripts'][$this->event]);
            }
        } elseif ($composer['scripts'][$this->event] === $this->command) {
            // If it's a single string, remove the event key entirely
            unset($composer['scripts'][$this->event]);
        }

        $filesystem->write(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists('composer.json')) {
            throw new \RuntimeException("Cannot remove script: 'composer.json' not found.");
        }
    }

    public function getDescription(): string
    {
        return "Removing composer script '{$this->command}' from '{$this->event}'";
    }
}
