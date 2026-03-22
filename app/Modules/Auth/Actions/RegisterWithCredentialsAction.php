<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\RegisterWithCredentialsData;
use App\Modules\Auth\Responses\LoginResponse;
use App\Modules\Users\DataTransferObjects\CreateUserData;

/** @final */
class RegisterWithCredentialsAction
{
    public function __construct(
        private readonly LoginAction $loginAction,
        private readonly RegisterUserAction $registerUserAction,
    ) {}

    public function execute(RegisterWithCredentialsData $data): LoginResponse
    {
        $user = $this->registerUserAction->execute(
            new CreateUserData(
                name: $data->name,
                email: $data->email,
                password: $data->password,
            ),
        );

        return $this->loginAction->execute(
            new LoginData(
                user: $user,
            )
        );
    }
}
