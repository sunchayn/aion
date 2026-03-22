<?php

namespace Aion\Stacks;

use Aion\Engine\Operations\OperationContract;
use Aion\Engine\PathResolver;

interface StackStrategyContract
{
    public function getName(): string;

    /**
     * @return OperationContract[]
     */
    public function getOperations(PathResolver $pathResolver): array;

    /**
     * @return string[]
     */
    public function getApplications(): array;

    public function getApiName(): string;

    public function getWebName(): string;
}
