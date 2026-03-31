<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

interface OperationContract
{
    /**
     * Get a description of what this operation does.
     */
    public function getDescription(): string;

    public function validate(FilesystemOperator $filesystem): void;

    public function execute(FilesystemOperator $filesystem): void;
}
