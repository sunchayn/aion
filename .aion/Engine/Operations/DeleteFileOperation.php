<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class DeleteFileOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath
    ) {}

    /**
     * @return self[]
     */
    public static function all(string|array $filePaths): array
    {
        return array_map(fn ($path) => new self($path), (array) $filePaths);
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        if ($filesystem->fileExists($this->filePath)) {
            $filesystem->delete($this->filePath);
        }
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // Deleting files is always safe even if they don't exist.
    }

    public function getDescription(): string
    {
        return "Deleting file: {$this->filePath}";
    }
}
