<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class MoveDirectoryOperation implements OperationContract
{
    public function __construct(
        private readonly string $source,
        private readonly string $destination
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->source)) {
            return;
        }

        $filesystem->move($this->source, $this->destination);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->source)) {
            throw new \RuntimeException("Source directory '{$this->source}' for move does not exist.");
        }
    }

    public function getDescription(): string
    {
        return "Moving directory from {$this->source} to {$this->destination}";
    }
}
