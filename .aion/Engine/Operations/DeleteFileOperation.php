<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class DeleteFileOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath
    ) {}

    public function getDescription(): string
    {
        return "Deleting file: {$this->filePath}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for deletion does not exist.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $filesystem->delete($this->filePath);
    }
}
