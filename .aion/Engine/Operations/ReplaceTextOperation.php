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

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            return;
        }

        $content = $filesystem->read($this->filePath);
        $content = str_replace($this->search, $this->replace, $content);

        $filesystem->write($this->filePath, $content);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->filePath)) {
            throw new \RuntimeException("Target file '{$this->filePath}' for replacement does not exist.");
        }
    }

    public function getDescription(): string
    {
        return "Making replacements in {$this->filePath}";
    }

    /**
     * @param  string[]|string  $filePaths
     * @return self[]
     */
    public static function all(array|string $filePaths, string $search, string $replace): array
    {
        $filePaths = (array) $filePaths;
        $operations = [];

        foreach ($filePaths as $filePath) {
            $operations[] = new self($filePath, $search, $replace);
        }

        return $operations;
    }
}
