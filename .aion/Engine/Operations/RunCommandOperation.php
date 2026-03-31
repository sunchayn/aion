<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RunCommandOperation implements OperationContract
{
    public function __construct(
        private readonly string $command,
        private readonly bool $quiet = false,
    ) {}

    public function getDescription(): string
    {
        return "Running command: {$this->command}";
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // Nothing to validate here.
    }

    public function execute(FilesystemOperator $filesystem): void
    {
        if (! $this->quiet) {
            passthru($this->command, $return);

            if ($return !== 0) {
                throw new \RuntimeException("Command '{$this->command}' failed with exit code {$return}.");
            }

            return;
        }

        exec($this->command, $output, $return);

        if ($return !== 0) {
            throw new \RuntimeException(($output[0] ?? "Command '{$this->command}' failed")." with exit code {$return}.");
        }
    }
}
