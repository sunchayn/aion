<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RemoveConfigBlockOperation implements OperationContract
{
    public function __construct(
        private readonly string $filePath,
        private readonly string $key
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            return;
        }

        $content = $filesystem->read($this->filePath);

        // This regex targets a key matching 'key' => [ ... ]
        // It matches the optional leading newline, indentation, the key, and the entire array block.
        $pattern = "/\n\s*'".preg_quote($this->key, '/')."'\s*=>\s*\[.*?\n\s{8}\],/s";

        $newContent = preg_replace($pattern, '', $content);

        if ($newContent !== $content && $newContent !== null) {
            $filesystem->write($this->filePath, $newContent);
        }
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for config block removal does not exist.");
        }
    }

    public function getDescription(): string
    {
        return "Removing config block '{$this->key}' from {$this->filePath}";
    }
}
