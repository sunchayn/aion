<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class CopyDirectoryOperation implements OperationContract
{
    private readonly string $source;

    private readonly string $destination;

    public function __construct(
        string $source,
        string $destination
    ) {
        $this->source = str_replace('\\', '/', $source);
        $this->destination = str_replace('\\', '/', $destination);
    }

    public function getDescription(): string
    {
        return "Copying directory from {$this->source} to {$this->destination}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->source)) {
            throw new \RuntimeException("Source directory '{$this->source}' does not exist.");
        }
    }

    public function execute(FilesystemOperator $filesystem): void
    {
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
}
