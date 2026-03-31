<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class ReplaceRegexOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath,
        private readonly string $pattern,
        private readonly string $replacement
    ) {}

    public function getDescription(): string
    {
        return "Making regex replacements in {$this->filePath}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for regex replacement does not exist.");
        }

        $content = $filesystem->read($this->filePath);

        if (preg_match($this->pattern, $content) === 0) {
            throw new \RuntimeException("Regex pattern '{$this->pattern}' not found in '{$this->filePath}'.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $content = $filesystem->read($this->filePath);
        $content = preg_replace($this->pattern, $this->replacement, $content);

        $filesystem->write($this->filePath, $content);
    }
}
