<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class ReplaceTextOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath,
        private readonly string $search,
        private readonly string $replace
    ) {}

    public function getDescription(): string
    {
        return "Making replacements in {$this->filePath}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for replacement does not exist.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for replacement does not exist.");
        }

        $content = $filesystem->read($this->filePath);
        $content = str_replace($this->search, $this->replace, $content);

        $filesystem->write($this->filePath, $content);
    }
}
