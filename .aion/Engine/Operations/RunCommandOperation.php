<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

use function Laravel\Prompts\error;

class RunCommandOperation implements OperationContract
{
    public function __construct(
        private readonly string $command,
        private readonly bool $quite = false,
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
        if ($this->quite) {
            exec($this->command, $output, $return);

            if ($return !== 0) {
                error($output[0] ?? 'Command didn\'t finish successfully!');

                return;
            }

            return;
        }

        passthru($this->command);
    }

    public function validate(FilesystemOperator $filesystem): void
    {
        // Commands are hard to validate without running them.
        // We could check if the binary exists if we parsed the command, but let's keep it simple.
    }

    public function getDescription(): string
    {
        return "Running command: {$this->command}";
    }
}
