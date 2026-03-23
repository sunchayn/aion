<?php

namespace Aion\Engine;

/**
 * Utility class to resolve paths within the Aion system.
 */
class PathResolver
{
    private const INTERNAL_DIR = '.aion';

    private const STUBS_DIR = '.aion/stubs';

    private const TEMP_DIR = '.aion/.temp';

    public function __construct(
        private readonly string $projectRoot
    ) {}

    /**
     * Resolve path relative to the target project root.
     */
    public function root(string $path = ''): string
    {
        return $this->join($this->projectRoot, $path);
    }

    /**
     * Resolve path relative to the .aion/stubs/ directory.
     */
    public function stub(string $path = ''): string
    {
        return $this->join(self::STUBS_DIR, $path);
    }

    /**
     * Resolve path relative to the .aion/ directory.
     */
    public function internal(string $path = ''): string
    {
        return $this->join(self::INTERNAL_DIR, $path);
    }

    /**
     * Resolve path for temporary files.
     */
    public function temp(string $path = ''): string
    {
        return $this->join(self::TEMP_DIR, $path);
    }

    private function join(string $base, string $path): string
    {
        $path = ltrim($path, '/');

        return $path === ''
            ? $base
            : rtrim($base, '/').'/'.$path;
    }
}
