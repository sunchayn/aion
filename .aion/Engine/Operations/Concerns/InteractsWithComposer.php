<?php

namespace Aion\Engine\Operations\Concerns;

use League\Flysystem\FilesystemOperator;
use RuntimeException;

trait InteractsWithComposer
{
    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    protected function updateComposer(FilesystemOperator $filesystem, string $action, callable $callback): void
    {
        $path = 'composer.json';

        if (! $filesystem->fileExists($path)) {
            throw new RuntimeException("Cannot {$action}: '{$path}' not found.");
        }

        $composer = json_decode($filesystem->read($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Failed to parse '{$path}': ".json_last_error_msg());
        }

        $composer = $callback($composer);

        $filesystem->write(
            $path,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }
}
