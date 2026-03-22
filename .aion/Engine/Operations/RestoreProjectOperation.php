<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

class RestoreProjectOperation implements OperationContract
{
    private const EXCLUSIONS = [
        'vendor',
        'node_modules',
        '.aion',
        '.git',
    ];

    public function __construct(
        private readonly string $backupPath = '.aion/.backup'
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->backupPath)) {
            return;
        }

        // 1. Delete current project files (excluding vendor, node_modules, .aion)
        $this->deleteRecursively($filesystem, '');

        // 2. Restore from backup
        $backupListing = $filesystem->listContents($this->backupPath, true);
        /** @var StorageAttributes $item */
        foreach ($backupListing as $item) {
            if ($item->isFile()) {
                $sourcePath = $item->path();
                $destinationPath = str_replace($this->backupPath.'/', '', $sourcePath);

                $filesystem->copy($sourcePath, $destinationPath);
            }
        }

        // 3. Cleanup backup
        $filesystem->deleteDirectory($this->backupPath);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        if (! $filesystem->directoryExists($this->backupPath)) {
            throw new \RuntimeException("Cannot restore: Backup directory '{$this->backupPath}' does not exist.");
        }
    }

    private function deleteRecursively(FilesystemOperator $filesystem, string $directory): void
    {
        $listing = $filesystem->listContents($directory ?: '.', false);

        /** @var StorageAttributes $item */
        foreach ($listing as $item) {
            $path = $item->path();

            if ($this->shouldExclude($path)) {
                continue;
            }

            if ($item->isFile()) {
                $filesystem->delete($path);
            } elseif ($item->isDir()) {
                $this->deleteRecursively($filesystem, $path);
            }
        }
    }

    private function shouldExclude(string $path): bool
    {
        foreach (self::EXCLUSIONS as $exclusion) {
            if ($path === $exclusion || str_starts_with($path, $exclusion.'/')) {
                return true;
            }
        }

        return false;
    }

    public function getDescription(): string
    {
        return 'Rolling back project changes from backup...';
    }
}
