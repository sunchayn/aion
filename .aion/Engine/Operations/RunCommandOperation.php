<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

class RunCommandOperation implements OperationContract
{
    public function __construct(
        private readonly string $command
    ) {}

    public function execute(FilesystemOperator $filesystem): void
    {
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
