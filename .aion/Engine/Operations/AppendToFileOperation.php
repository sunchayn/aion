<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class AppendToFileOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath,
        private readonly string $content
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            return;
        }

        $content = $filesystem->read($this->filePath);

        $content = rtrim($content)."\n\n".ltrim($this->content)."\n";

        $filesystem->write($this->filePath, $content);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for appending does not exist.");
        }
    }

    public function getDescription(): string
    {
        return "Appending content to {$this->filePath}";
    }
}
