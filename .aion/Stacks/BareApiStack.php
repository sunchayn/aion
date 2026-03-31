<?php

namespace Aion\Stacks;

use Aion\Engine\PathResolver;

class BareApiStack implements StackStrategyContract
{
    public function __construct() {}

    public function getName(): string
    {
        return 'Bare API Stack';
    }

    public function getDescription(): string
    {
        return 'A clean, backend-only API application.';
    }

    public function getApplications(): array
    {
        return [$this->getApiName(), $this->getWebName()];
    }

    public function getApiName(): string
    {
        return 'Api';
    }

    public function getWebName(): string
    {
        return 'Web';
    }

    public function getOperations(PathResolver $pathResolver): array
    {
        return [];
    }
}
