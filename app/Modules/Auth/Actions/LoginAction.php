<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Responses\LoginResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\StatefulGuard;

/** @final */
class LoginAction
{
    public function __construct(
        private readonly StatelessLoginAction $statelessLoginAction,
        private readonly StatefulLoginAction $statefulLoginAction,
        private readonly AuthManager $authManager,
    ) {}

    public function execute(LoginData $data): LoginResponse
    {
        $loginData = new LoginData(
            user: $data->user,
            guard: $data->guard,
            remember: $data->remember,
            skipTwoFactor: $data->skipTwoFactor,
        );

        if ($this->authManager->guard($data->guard) instanceof StatefulGuard) {
            return $this->statefulLoginAction->execute($loginData);
        }

        return $this->statelessLoginAction->execute($loginData);
    }
}
