<?php

namespace App\Modules\Auth\Actions;

use Illuminate\Auth\AuthManager;

/** @final */
class LogoutAction
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    public function execute(): void
    {
        $this->authManager->logout();
    }
}
