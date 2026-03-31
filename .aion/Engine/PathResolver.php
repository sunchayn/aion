<?php

namespace Aion\Engine;

class PathResolver
{
    private const string INTERNAL_DIR = '.aion';

    private const string STUBS_DIR = '.aion/stubs';

    public function __construct(
        private string $projectRoot
    ) {
        $this->projectRoot = $this->ensureNoTrailingSlashes(str_replace('\\', '/', $this->projectRoot));
    }

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

    private function join(string $base, string $path): string
    {
        $path = ltrim($this->ensureNoTrailingSlashes(str_replace('\\', '/', $path)), '/');

        return $path === '' ? $base : "{$base}/{$path}";
    }

    private function ensureNoTrailingSlashes(string $path): string
    {
        return rtrim($path, '/\\');
    }
}
