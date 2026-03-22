<?php

namespace Aion\Features;

use Aion\Engine\AionConfig;
use Closure;

/**
 * Defines a single configuration option for a feature.
 */
class OptionDefinition
{
    /**
     * @param  string  $label  The label for the prompt.
     * @param  string  $type  The type of prompt (confirm, select, multiselect).
     * @param  mixed  $default  The default value.
     * @param  array<string, string>  $options  Available options for select/multiselect.
     * @param  string|null  $hint  A hint for the user.
     * @param  (Closure(): bool)|null  $shouldSkip  A closure to check the option eligibility.
     * @param  Closure|null  $transformer  A closure to transform the raw input into a type-safe value.
     */
    public function __construct(
        public string $label,
        public string $type,
        public mixed $default,
        public array $options = [],
        public ?string $hint = null,
        public ?Closure $shouldSkip = null,
        public ?Closure $transformer = null,
    ) {}

    /**
     * Transform the raw value using the provided closure.
     */
    public function transform(mixed $value): mixed
    {
        return $this->transformer
            ? ($this->transformer)($value)
            : $value;
    }

    public function shouldSkip(AionConfig $config): bool
    {
        if ($this->shouldSkip !== null) {
            return ($this->shouldSkip)($config);
        }

        return false;
    }
}
