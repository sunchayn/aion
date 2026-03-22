<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

class BackupProjectOperation implements OperationContract
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
        if ($filesystem->directoryExists($this->backupPath)) {
            $filesystem->deleteDirectory($this->backupPath);
        }

        $this->copyRecursively($filesystem, '');
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // No pre-requisites for backup, it will overwrite any existing backup folder.
    }

    private function copyRecursively(FilesystemOperator $filesystem, string $directory): void
    {
        $listing = $filesystem->listContents($directory ?: '.', false);

        /** @var StorageAttributes $item */
        foreach ($listing as $item) {
            $path = $item->path();

            if ($this->shouldExclude($path)) {
                continue;
            }

            if ($item->isFile()) {
                $destination = $this->backupPath.'/'.$path;
                $filesystem->copy($path, $destination);
            } elseif ($item->isDir()) {
                $this->copyRecursively($filesystem, $path);
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
        return 'Creating a backup of the project...';
    }
}
