<?php

namespace Aion\Commands\UI;

use Aion\Engine\AionConfig;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

class SparkUI
{
    public function __construct(
        private readonly OutputInterface $output
    ) {}

    public function displayHeader(): void
    {
        $this->showHeaderWithAnimation();

        $this->showTitle('AION Starter Kit configuration wizard');
    }

    public function showTitle(string $title): void
    {
        $spaces = strlen($title) + 4;

        $this->output->writeln('<fg=green;options=bold>┌'.str_repeat(' ', $spaces).'┐</>');
        $this->output->writeln("  <bg=green;fg=white;options=bold> {$title} </>  ");
        $this->output->writeln('<fg=green;options=bold>└'.str_repeat(' ', $spaces).'┘</>');
    }

    /**
     * @param  array<string, OptionDefinition>  $optionDefinitions
     */
    public function displaySummary(
        StackStrategyContract $stack,
        AionConfig $config,
        array $optionDefinitions
    ): void {
        $this->showTitle('Selected Kit Options');

        $table = new Table($this->output);
        $table->setHeaders(['Label', 'Value']);

        $table->addRow(['Stack', $stack->getName()]);
        $table->addRow(['Description', "<comment>{$stack->getDescription()}</comment>"]);
        $table->addRow(['<fg=green;options=bold>Application Names</>', implode(', ', $stack->getApplications())]);
        $table->addRow(new TableSeparator);

        $this->addFeatureRows($table, $config, $optionDefinitions);

        $table->setStyle('box');
        $table->render();

        $this->output->writeln('');
    }

    public function displayProgress(iterable $progressSteps): void
    {
        $titleSection = $this->output->section();
        $titleSection->overwrite('Putting things together...');

        $progressSection = $this->output->section();

        foreach ($progressSteps as $stepDescription) {
            $progressSection->overwrite("> $stepDescription");

            $this->sleep(rand(50_000, 150_000));
        }

        $progressSection->overwrite('100%');
        $titleSection->overwrite('Putting things together... Done!');
        $this->sleep(350_000);

        $this->output->writeln('');
    }

    public function displaySuccess(string $message): void
    {
        $this->output->writeln("\n<info>>_ ✨ $message</info>");
    }

    /**
     * @param  array<string, OptionDefinition>  $schemas
     */
    private function addFeatureRows(Table $table, AionConfig $config, array $schemas): void
    {
        $rows = [];
        foreach ($schemas as $key => $definition) {
            if (! $config->has($key)) {
                continue;
            }

            $value = $config->get($key);
            $rows[] = [
                "<fg=green;options=bold>{$definition->label}</>",
                $this->formatValue($value),
            ];
        }

        foreach ($rows as $index => $row) {
            $table->addRow($row);
            if ($index < count($rows) - 1) {
                $table->addRow(new TableSeparator);
            }
        }
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => $this->formatSingleValue($v), $value));
        }

        return $this->formatSingleValue($value);
    }

    private function formatSingleValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \UnitEnum) {
            return method_exists($value, 'label') ? $value->label() : ($value->value ?? $value->name);
        }

        return (string) $value;
    }

    private function sleep(int $microseconds): void
    {
        if ($this->output->isQuiet() || ! $this->output->isDecorated()) {
            return;
        }

        usleep($microseconds);
    }

    private function showHeaderWithAnimation(): void
    {
        $logo = [
            '<fg=blue>*</>  <fg=green>  █████╗ ██╗ ██████╗ ███╗   ██╗</>          <fg=yellow;options=bold>*</> <fg=cyan>.</>  <fg=magenta;options=bold>*</>  <fg=cyan>.</>  <fg=yellow;options=bold>*</>  <fg=cyan>.</> <fg=magenta>*</>',
            ' <fg=cyan>.</> <fg=green> ██╔══██╗██║██╔═══██╗████╗  ██║</>      <fg=cyan>.</> <fg=cyan>  .  :  .  </><fg=cyan> .  </><fg=yellow>*</><fg=cyan> .</>',
            '<fg=yellow>*</>  <fg=green;options=bold> ███████║██║██║   ██║██╔██╗ ██║</>   <fg=cyan;options=bold>  .  :  <fg=yellow;options=bold>*</>  :  .  </><fg=magenta;options=bold> .  *</>',
            ' <fg=cyan>.</> <fg=green;options=bold> ██╔══██║██║██║   ██║██║╚██╗██║</>      <fg=cyan>.</> <fg=cyan>  .  :  .  </><fg=cyan> .  </><fg=yellow>*</><fg=cyan> .</>',
            '<fg=blue>*</>  <fg=green> ██║  ██║██║╚██████╔╝██║ ╚████║</>          <fg=yellow;options=bold>*</> <fg=cyan>.</>  <fg=magenta;options=bold>*</>  <fg=cyan>.</>  <fg=yellow;options=bold>*</>  <fg=cyan>.</> <fg=magenta>*</>',
            '   <fg=green> ╚═╝  ╚═╝╚═╝ ╚═════╝ ╚═╝  ╚═══╝</>                             ',
            '      <fg=cyan>.</>         <fg=yellow>*</>          <fg=cyan>.</>          <fg=magenta;options=bold>*</>          <fg=blue>.</>   ',
        ];

        $this->output->writeln('');

        foreach ($logo as $line) {
            $this->output->writeln($line);
            $this->sleep(40_000);
        }

        $this->sleep(100_000);

        $subtitle = '   S T A R T E R   K I T';
        $this->output->write(str_repeat(' ', 8));

        foreach (mb_str_split($subtitle) as $char) {
            $this->output->write("<fg=green;options=bold>$char</>");
            $this->sleep(20_000);
        }

        $this->output->writeln("\n");
        $this->sleep(200_000);
    }
}
