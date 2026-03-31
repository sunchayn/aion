<?php

namespace Aion\Engine;

use Aion\Choices\Enums\ConfigKeyEnum;

/**
 * A type-safe configuration container for Aion project generation.
 */
class AionConfig
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function add(string|ConfigKeyEnum $key, mixed $value): self
    {
        $key = $key instanceof ConfigKeyEnum ? $key->value : $key;

        $this->config[$key] = $value;

        return $this;
    }

    public function get(string|ConfigKeyEnum $key, mixed $default = null): mixed
    {
        $key = $key instanceof ConfigKeyEnum ? $key->value : $key;

        return $this->config[$key] ?? $default;
    }

    public function string(ConfigKeyEnum $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function bool(ConfigKeyEnum $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * @return array<mixed>
     */
    public function array(ConfigKeyEnum $key, array $default = []): array
    {
        return (array) $this->get($key, $default);
    }

    public function has(string|ConfigKeyEnum $key): bool
    {
        $key = $key instanceof ConfigKeyEnum ? $key->value : $key;

        return array_key_exists($key, $this->config);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }
}
