<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class CopyFileOperation implements OperationContract
{
    public function __construct(
        private readonly string $source,
        private readonly string $destination,
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if ($filesystem->fileExists($this->destination)) {
            $filesystem->delete($this->destination);
        }

        $filesystem->copy($this->source, $this->destination);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->fileExists($this->source)) {
            throw new \RuntimeException("Source file '{$this->source}' does not exist.");
        }
    }

    public function getDescription(): string
    {
        return 'Copying '.basename($this->source).' to '.$this->destination;
    }
}
