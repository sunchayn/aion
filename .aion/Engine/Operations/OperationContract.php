<?php

namespace Aion\Engine\Operations;

use League\Flysystem\FilesystemOperator;

interface OperationContract
{
    public function execute(FilesystemOperator $filesystem): void;

    public function validate(FilesystemOperator $filesystem): void;

    /**
     * Get a description of what this operation does.
     */
    public function getDescription(): string;
}
