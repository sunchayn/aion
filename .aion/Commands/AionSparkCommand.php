<?php

namespace Aion\Commands;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Commands\Prompters\ConfigurationPrompter;
use Aion\Commands\UI\SparkUI;
use Aion\Engine\AionConfig;
use Aion\Engine\Engine;
use Aion\Engine\FeatureRegistry;
use Aion\Engine\PathResolver;
use Aion\Engine\PromptTypeEnum;
use Aion\Features\Authentication\ApiTokensFeature;
use Aion\Features\Authentication\OAuthFeature;
use Aion\Features\ExternalTools\ECSLoggingFeature;
use Aion\Features\OptionDefinition;
use Aion\Features\System\AgenticAiSystemFeature;
use Aion\Features\System\DatabaseSystemFeature;
use Aion\Features\System\LogSystemFeature;
use Aion\Features\System\PhpStanSystemFeature;
use Aion\Stacks\StackStrategyContract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class AionSparkCommand extends Command
{
    protected string $name = 'aion:spark';

    protected static FeatureRegistry $featureRegistry;

    protected SparkUI $ui;

    private ConfigurationPrompter $configurationPrompter;

    private bool $isInteractive = false;

    private OutputInterface $output;

    protected function configure(): void
    {
        $this->setName($this->name)
            ->addOption('dry-run', description: 'Execute a dry run of the bake process.')
            ->addOption(
                ConfigKeyEnum::Frontend->value,
                mode: InputOption::VALUE_NEGATABLE,
                description: $this->getWhetherWantToUseFrontendOptionDefinition()->label,
            );

        $this->bootstrapFeatureRegistry();
    }

    private static function getWhetherWantToUseFrontendOptionDefinition(): OptionDefinition
    {
        return new OptionDefinition(
            label: 'Do you need a Frontend?',
            type: PromptTypeEnum::Confirm,
            default: false,
            hint: 'Determines the technology stack for your application.',
        );
    }

    private function bootstrapFeatureRegistry(): void
    {
        if (isset(self::$featureRegistry)) {
            return;
        }

        self::$featureRegistry = new FeatureRegistry;

        self::$featureRegistry->registerFeatures([
            ApiTokensFeature::class,
            OAuthFeature::class,
            ECSLoggingFeature::class,
            LogSystemFeature::class,
            DatabaseSystemFeature::class,
            PhpStanSystemFeature::class,
            AgenticAiSystemFeature::class,
        ]);

        $this->addDynamicOptionsFromFeatures();
    }

    private function addDynamicOptionsFromFeatures(): void
    {
        foreach (self::$featureRegistry->getOptionDefinitions() as $key => $definition) {
            if ($this->getDefinition()->hasOption($key)) {
                continue;
            }

            $type = $definition->type === PromptTypeEnum::Confirm
                ? InputOption::VALUE_NEGATABLE
                : InputOption::VALUE_REQUIRED;

            $this->addOption($key, null, $type, $definition->label);
        }
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->bootstrapFeatureRegistry();
        $this->ui = new SparkUI($output);
        $this->configurationPrompter = new ConfigurationPrompter($input);
        $this->isInteractive = $input->isInteractive();

        $this->ui->displayHeader();

        [$stackStrategy, $aionConfig] = $this->promptForConfiguration();

        $this->runCompositionEngine(
            $stackStrategy,
            $aionConfig,
            dryRun: (bool) $input->getOption('dry-run'),
        );

        if (! $input->getOption('dry-run')) {
            $this->installDependencies($aionConfig);
        }

        $this->ui->displaySuccess('Setup is complete. Enjoy your new Aion-backed project!'.($input->getOption('dry-run') ? ' (Dry Run Complete)' : ''));

        if (PHP_OS_FAMILY === 'Windows' && ! $input->getOption('dry-run')) {
            info('>_ Note: On Windows, you should manually delete the .aion folder to finish the cleanup.');
        }

        return self::SUCCESS;
    }

    /** @return array{0: StackStrategyContract, 1: AionConfig} */
    private function promptForConfiguration(): array
    {
        while (true) {
            $stackDefinition = $this->getWhetherWantToUseFrontendOptionDefinition();

            $stack = $this->resolveStack($stackDefinition);

            $aionConfig = $this->resolveAionConfig();

            if ($this->confirmSetup($stack, $aionConfig, $stackDefinition)) {
                return [$stack, $aionConfig];
            }

            $this->output->writeln("\n<fg=yellow>Restarting configuration...</>\n");
        }
    }

    private function resolveStack(OptionDefinition $definition): StackStrategyContract
    {
        $this->output->writeln("\n<comment>>_ First, let's pick your technology stack.</comment>");

        return $this->configurationPrompter->promptForStack($definition);
    }

    private function resolveAionConfig(): AionConfig
    {
        $this->output->writeln("\n<comment>>_ Awesome. Let's configure your application.</comment>\n");

        return $this->configurationPrompter->promptForConfiguration(
            optionDefinitions: self::$featureRegistry->getOptionDefinitions(),
        );
    }

    private function confirmSetup(StackStrategyContract $stack, AionConfig $config, OptionDefinition $stackDefinition): bool
    {
        if ($this->isInteractive === false) {
            return true;
        }

        $this->ui->displaySummary(
            stack: $stack,
            config: $config,
            optionDefinitions: [
                ConfigKeyEnum::Frontend->value => $stackDefinition,
                ...self::$featureRegistry->getOptionDefinitions(),
            ],
        );

        return confirm(label: 'Is this configuration correct?');
    }

    /** @throws Throwable */
    private function runCompositionEngine(
        StackStrategyContract $stackStrategy,
        AionConfig $aionConfig,
        bool $dryRun = false,
    ): void {
        $engine = new Engine(
            featureRegistry: self::$featureRegistry,
            stack: $stackStrategy,
            configuration: $aionConfig,
            pathResolver: new PathResolver(getcwd()),
            dryRun: $dryRun,
        );

        $this->ui->displayProgress(
            progressSteps: $engine->yieldBakingOperations(),
        );
    }

    private function installDependencies(AionConfig $config): void
    {
        if ($this->isInteractive && ! confirm('Would you like to install the composer dependencies now?')) {
            return;
        }

        if (file_exists('.env.example') && ! file_exists('.env')) {
            copy('.env.example', '.env');
        }

        passthru('composer install --no-scripts');
        passthru('php artisan key:generate');

        if ($this->isInteractive && $config->bool(ConfigKeyEnum::AgenticAi, true)) {
            passthru('php artisan boost:install');
        }

        $this->output->writeln('');
    }
}
