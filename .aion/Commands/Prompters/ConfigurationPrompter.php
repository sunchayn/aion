<?php

namespace Aion\Commands\Prompters;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Engine\AionConfig;
use Aion\Features\OptionDefinition;
use Aion\Stacks\ApiWithDefaultFrontEndSupportStack;
use Aion\Stacks\BareApiStack;
use Aion\Stacks\StackStrategyContract;
use Symfony\Component\Console\Input\InputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ConfigurationPrompter
{
    public function __construct(
        private readonly InputInterface $input
    ) {}

    public function promptForStack(): StackStrategyContract
    {
        $requireFe = $this->handleConfirmation(
            option: ConfigKeyEnum::Frontend,
            label: 'Do you need a Frontend?',
            displayName: 'Using Frontend',
        );

        return $requireFe
            ? new ApiWithDefaultFrontEndSupportStack
            : new BareApiStack;
    }

    /**
     * @param  array<string, OptionDefinition>  $optionDefinitions
     */
    public function promptForConfiguration(
        array $optionDefinitions,
    ): AionConfig {
        $config = new AionConfig;

        foreach ($optionDefinitions as $key => $definition) {
            if ($definition->shouldSkip($config)) {
                continue;
            }

            $config->add($key, $this->askQuestion($key, $definition));
        }

        return $config;
    }

    private function askQuestion(string|ConfigKeyEnum $key, OptionDefinition $definition): mixed
    {
        $value = match ($definition->type) {
            'confirm' => $this->handleConfirmation(
                option: $key,
                label: $definition->label,
                displayName: $definition->label,
                default: $definition->default,
                hint: $definition->hint,
            ),
            'select', 'multiselect' => $this->handleSelection(
                option: $key,
                label: $definition->label,
                options: $definition->options,
                default: $definition->default,
                displayName: $definition->label,
                isMulti: $definition->type === 'multiselect',
                hint: $definition->hint,
            ),
            default => throw new \InvalidArgumentException("Unsupported question type: {$definition->type}"),
        };

        return $definition->transform($value);
    }

    private function handleConfirmation(
        string|ConfigKeyEnum $option,
        string $label,
        string $displayName,
        bool $default = false,
        ?string $hint = null,
    ): bool {
        if ($predefined = $this->getPredefinedValue($option)) {
            info("> Using pre-configured $displayName: ".($predefined === '1' || $predefined === true ? 'Yes' : 'No'));

            return filter_var($predefined, FILTER_VALIDATE_BOOLEAN);
        }

        if (! $this->input->isInteractive()) {
            info("> Using pre-defined value for <$displayName>: ".($default === true ? 'Yes' : 'No'));

            return $default;
        }

        return confirm(label: $label, default: $default, hint: $hint ?? '');
    }

    private function handleSelection(
        string|ConfigKeyEnum $option,
        string $label,
        array $options,
        mixed $default,
        string $displayName,
        bool $isMulti = false,
        ?string $hint = null
    ): mixed {
        if ($predefined = $this->getPredefinedValue($option)) {
            $parsed = $isMulti ? explode(',', (string) $predefined) : $predefined;
            $display = $isMulti ? implode(', ', (array) $parsed) : (string) $parsed;

            info("> Using pre-defined value for <$displayName>: $display");

            return $parsed;
        }

        if (! $this->input->isInteractive()) {
            $display = $isMulti ? implode(', ', $default) : $default;

            info("> Using default value for <$displayName>: $display");

            return $default;
        }

        return $isMulti
            ? multiselect(label: $label, options: $options, default: $default, required: true, hint: $hint ?? '')
            : select(label: $label, options: $options, default: $default, hint: $hint ?? '');
    }

    private function getPredefinedValue(string|ConfigKeyEnum $option): mixed
    {
        $key = $option instanceof ConfigKeyEnum ? $option->value : $option;

        return $this->input->getOption($key);
    }
}
