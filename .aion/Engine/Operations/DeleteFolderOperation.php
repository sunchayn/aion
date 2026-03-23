<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class DeleteFolderOperation implements OperationContract
{
    public function __construct(
        private readonly string $path
    ) {}

    /**
     * @return self[]
     */
    public static function all(string|array $paths): array
    {
        return array_map(fn ($path) => new self($path), (array) $paths);
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        $this->deleteRecursively($filesystem, $this->path);
    }


    private function deleteRecursively(FilesystemOperator $filesystem, string $path): void
    {
        if (! $filesystem->directoryExists($path)) {
            return;
        }

        // First delete the folder content
        // to support deletion cross platforms when the script is running from the directory.
        foreach ($filesystem->listContents($path, deep: false) as $item) {
            if ($item->isDir()) {
                $this->deleteRecursively($filesystem, $item->path());

                continue;
            }

            $filesystem->delete($item->path());
        }

        $filesystem->deleteDirectory($path);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // Deleting folders is always safe even if they don't exist.
    }

    public function getDescription(): string
    {
        return "Deleting folder: {$this->path}";
    }
}
