<?php

namespace Aion\Commands;

use Aion\Commands\UI\SparkUI;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class AionSandboxCommand extends Command
{
    protected string $name = 'aion:sandbox';

    private const string INTERACTIVE_PROFILE = 'interactive';

    private const string INTERACTIVE_PROFILE_LABEL = 'no profile (interactive mode)';

    private SparkUI $ui;

    protected function configure(): void
    {
        $this->setName($this->name)
            ->setDescription('Verify Aion project generation across different stack combinations.')
            ->addArgument('profile', InputArgument::OPTIONAL, 'The profile to run (api-stateless, web-stateful, api-stateless-full, api-stateful-lite)')
            ->addOption('matrix', null, InputOption::VALUE_NONE, 'Run all defined profiles in sequence.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute a dry run of the process.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleOutputInterface $output */
        $this->ui = new SparkUI($output);
        $profiles = $this->getProfiles();

        if ($input->getOption('matrix')) {
            $this->runMatrix($profiles, $input, $output);

            return self::SUCCESS;
        }

        $profileName = $input->getArgument('profile') ?: $this->promptForProfile(array_keys($profiles));

        try {
            if ($profileName === self::INTERACTIVE_PROFILE_LABEL) {
                $this->runProfile(self::INTERACTIVE_PROFILE, [], $input, $output);

                return self::SUCCESS;
            }

            $options = $profiles[$profileName] ?? [];

            $this->runProfile($profileName, $options, $input, $output);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function promptForProfile(array $profileNames): string
    {
        $options = array_merge([self::INTERACTIVE_PROFILE_LABEL], $profileNames);

        return select(
            label: 'Which profile would you like to run?',
            options: $options,
            default: $options[0]
        );
    }

    private function runMatrix(array $profiles, InputInterface $input, OutputInterface $output): void
    {
        info('Profiles to run: '.implode(', ', array_keys($profiles)));

        foreach ($profiles as $name => $options) {
            $this->runProfile($name, $options, $input, $output);
        }

        $this->ui->showTitle('Matrix testing complete!');
    }

    private function runProfile(string $name, array $options, InputInterface $input, OutputInterface $output): void
    {
        $dryRun = (bool) $input->getOption('dry-run');

        $this->ui->showTitle("Running Profile: {$name}");

        if (! empty($options)) {
            $output->writeln(' <fg=gray>Options: '.implode(' ', $options).'</>');
            $output->writeln('');
        }

        $sandboxDir = ".aion/.sandbox/{$name}";

        $this->prepareSandbox($sandboxDir, $output);
        $this->installDependencies($sandboxDir, $output);
        $this->executeSpark($sandboxDir, $options, $dryRun, $name, $output);

        if (! $dryRun) {
            $this->runTests($sandboxDir, $name, $output);
        }

        info("SUCCESS: Finished {$name}");
    }

    private function prepareSandbox(string $sandboxDir, OutputInterface $output): void
    {
        $output->writeln(" <fg=yellow>></> Cleaning previous sandbox: {$sandboxDir}");

        if (is_dir($sandboxDir)) {
            $this->exec('rm -rf '.escapeshellarg($sandboxDir));
        }

        mkdir($sandboxDir, 0755, true);

        $output->writeln(' <fg=yellow>></> Mirroring project to sandbox...');

        $this->exec(sprintf(
            'rsync -a --exclude=".aion/.sandbox" --exclude="vendor" --exclude="node_modules" --exclude="composer.lock" ./ %s/',
            escapeshellarg($sandboxDir)
        ));
    }

    private function installDependencies(string $sandboxDir, OutputInterface $output): void
    {
        $output->writeln(' <fg=yellow>></> Installing dependencies...');

        $this->exec('composer install --no-scripts --quiet', $sandboxDir);
    }

    private function executeSpark(string $sandboxDir, array $options, bool $dryRun, string $profileName, OutputInterface $output): void
    {
        $sparkOptions = implode(' ', $options);

        $output->writeln(" <fg=yellow>></> Execution: php .aion/spark {$sparkOptions}");

        $command = ['php', '.aion/spark'];

        $command = array_merge($command, $options);

        if ($profileName !== self::INTERACTIVE_PROFILE) {
            $command[] = '--no-interaction';
        }

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $this->exec(implode(' ', $command), $sandboxDir);
    }

    private function runTests(string $sandboxDir, string $name, OutputInterface $output): void
    {
        $output->writeln(' <fg=yellow>></> Running tests...');

        $this->exec('php artisan test --parallel', cwd: $sandboxDir);
    }

    private function exec(string $command, ?string $cwd = null): void
    {
        $originalCwd = getcwd();

        if ($cwd) {
            chdir($cwd);
        }

        passthru($command, $result);

        if ($cwd) {
            chdir($originalCwd);
        }

        if ($result !== 0) {
            throw new RuntimeException("Command failed with exit code {$result}: {$command}");
        }
    }

    /** @return array<string, array<string>> */
    private function getProfiles(): array
    {
        return [
            'api-stateless' => ['--no-frontend', '--api-type=stateless', '--logging-structure=default'],
            'web-stateful' => ['--frontend', '--api-type=stateful', '--logging-structure=default', '--oauth', '--oauth-providers=google'],
            'api-stateless-full' => ['--no-frontend', '--api-type=stateless', '--db=mysql,pgsql', '--logging-structure=ecs', '--phpstan=9', '--oauth', '--oauth-providers=google,github,apple'],
            'api-stateful-lite' => ['--no-frontend', '--api-type=stateful', '--db=sqlite', '--logging-structure=default', '--logs=stderr', '--phpstan=5'],
            'oauth-full' => ['--no-frontend', '--api-type=stateless', '--oauth', '--oauth-providers=google,github,apple'],
        ];
    }
}
