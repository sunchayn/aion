<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class DeleteFolderOperation implements OperationContract
{
    public function __construct(
        private readonly string $path
    ) {}

    public function getDescription(): string
    {
        return "Deleting folder: {$this->path}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->path)) {
            throw new \RuntimeException("Target folder '{$this->path}' for deletion does not exist.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $filesystem->deleteDirectory($this->path);
    }
}
