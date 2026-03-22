<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class CopyDirectoryOperation implements OperationContract
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

        $listing = $filesystem->listContents($this->source, true);

        foreach ($listing as $item) {
            $sourcePath = $item->path();
            $destinationPath = str_replace($this->source, $this->destination, $sourcePath);

            if ($item->isFile()) {
                if ($filesystem->fileExists($destinationPath)) {
                    $filesystem->delete($destinationPath);
                }
                $filesystem->copy($sourcePath, $destinationPath);
            }
        }
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->source)) {
            throw new \RuntimeException("Source directory '{$this->source}' does not exist.");
        }
    }

    public function getDescription(): string
    {
        return "Copying directory from {$this->source} to {$this->destination}";
    }
}
