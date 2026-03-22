<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class DeleteFolderOperation implements OperationContract
{
    public function __construct(
        private readonly string $path
    ) {}

    /**
     * @return self[]
     */
    public static function all(string|array $paths): array
    {
        return array_map(fn ($path) => new self($path), (array) $paths);
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        if ($filesystem->directoryExists($this->path)) {
            $filesystem->deleteDirectory($this->path);
        }
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // Deleting folders is always safe even if they don't exist.
    }

    public function getDescription(): string
    {
        return "Deleting folder: {$this->path}";
    }
}
