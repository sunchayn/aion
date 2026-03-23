<?php

namespace Aion\Commands;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Commands\Prompters\ConfigurationPrompter;
use Aion\Commands\UI\SparkUI;
use Aion\Engine\AionConfig;
use Aion\Engine\Engine;
use Aion\Engine\FeatureRegistry;
use Aion\Engine\PathResolver;
use Aion\Features\Authentication\ApiTokensFeature;
use Aion\Features\Authentication\OAuthFeature;
use Aion\Features\ExternalTools\ECSLoggingFeature;
use Aion\Features\System\DatabaseSystemFeature;
use Aion\Features\System\LogSystemFeature;
use Aion\Features\System\PhpStanSystemFeature;
use Aion\Stacks\ApiWithDefaultFrontEndSupportStack;
use Aion\Stacks\BareApiStack;
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

    protected function configure(): void
    {
        $this->setName($this->name)
            ->addOption('dry-run', description: 'Execute a dry run of the bake process.')
            ->addOption(ConfigKeyEnum::Frontend->value, mode: InputOption::VALUE_NEGATABLE, description: 'Include frontend support.');

        $this->bootstrapFeatureRegistry();

        $this->addDynamicOptionsFromFeatures();
    }

    private function bootstrapFeatureRegistry(): void
    {
        if (isset(self::$featureRegistry)) {
            return;
        }

        self::$featureRegistry = new FeatureRegistry;

        self::$featureRegistry->registerStacks([
            BareApiStack::class,
            ApiWithDefaultFrontEndSupportStack::class,
        ]);

        self::$featureRegistry->registerFeatures([
            ApiTokensFeature::class,
            OAuthFeature::class,
            ECSLoggingFeature::class,
            LogSystemFeature::class,
            DatabaseSystemFeature::class,
            PhpStanSystemFeature::class,
        ]);
    }

    private function addDynamicOptionsFromFeatures(): void
    {
        foreach (self::$featureRegistry->getOptionDefinitions() as $key => $definition) {
            if ($this->getDefinition()->hasOption($key)) {
                continue;
            }

            $type = $definition->type === 'confirm'
                ? InputOption::VALUE_NEGATABLE
                : InputOption::VALUE_REQUIRED;

            $this->addOption($key, null, $type, $definition->label);
        }
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrapFeatureRegistry();
        $this->ui = new SparkUI($output);
        $this->configurationPrompter = new ConfigurationPrompter($input);
        $this->isInteractive = $input->isInteractive();

        $this->ui->displayHeader();

        [$stackStrategy, $aionConfig] = $this->promptForConfiguration($output);

        $this->runEngine(
            $stackStrategy,
            $aionConfig,
            $output,
            dryRun: (bool) $input->getOption('dry-run'),
        );

        if (! $input->getOption('dry-run')) {
            $this->installDependencies($output);
        }

        info('>_ ✨ Setup is complete. Enjoy your new Aion-backed project!'.($input->getOption('dry-run') ? ' (Dry Run Complete)' : ''));

        return self::SUCCESS;
    }

    /** @return array{0: StackStrategyContract, 1: AionConfig} */
    private function promptForConfiguration(OutputInterface $output): array
    {
        while (true) {
            $stack = $this->resolveStack($output);
            $aionConfig = $this->resolveAionConfig($output);

            if ($this->confirmSetup($stack, $aionConfig)) {
                return [$stack, $aionConfig];
            }

            $output->writeln("\n<fg=yellow>Restarting configuration...</>\n");
        }
    }

    private function resolveStack(OutputInterface $output): StackStrategyContract
    {
        $output->writeln("\n<comment>>_ First, let's pick your technology stack.</comment>");

        return $this->configurationPrompter->promptForStack();
    }

    private function resolveAionConfig(OutputInterface $output): AionConfig
    {
        $output->writeln("\n<comment>>_ Awesome. Let's configure your application.</comment>\n");

        return $this->configurationPrompter->promptForConfiguration(
            optionDefinitions: self::$featureRegistry->getOptionDefinitions(),
        );
    }

    private function confirmSetup(StackStrategyContract $stack, AionConfig $config): bool
    {
        if ($this->isInteractive === false) {
            return true;
        }

        $this->ui->displaySummary(
            stack: $stack,
            config: $config,
            optionDefinitions: self::$featureRegistry->getOptionDefinitions(),
        );

        return confirm(label: 'Is this configuration correct?');
    }

    /** @throws Throwable */
    private function runEngine(
        StackStrategyContract $stackStrategy,
        AionConfig $aionConfig,
        OutputInterface $output,
        bool $dryRun = false,
    ): void {
        $engine = new Engine(
            registry: self::$featureRegistry,
            stack: $stackStrategy,
            configuration: $aionConfig,
            pathResolver: new PathResolver(getcwd()),
            dryRun: $dryRun,
        );

        $titleSection = $output->section();
        $titleSection->overwrite('Putting things together...');

        $progressSection = $output->section();

        $engine->bake(function (string $description) use ($progressSection) {
            $progressSection->overwrite("> $description");

            usleep(rand(50_000, 150_000));
        });

        $progressSection->overwrite('100%');
        $titleSection->overwrite('Putting things together... Done!');
        usleep(350_000);

        $output->writeln('');
    }

    private function installDependencies(OutputInterface $output): void
    {
        if ($this->isInteractive && ! confirm('Would you like to install the composer dependencies now?')) {
            return;
        }

        if (file_exists('.env.example') && ! file_exists('.env')) {
            copy('.env.example', '.env');
        }

        passthru('composer install --no-scripts');
        passthru('php artisan key:generate');

        if ($this->isInteractive) {
            passthru('php artisan boost:install');
        }

        $output->writeln('');
    }
}
