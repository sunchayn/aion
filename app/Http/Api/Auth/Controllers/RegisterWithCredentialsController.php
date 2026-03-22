<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\RegisterWithCredentialsRequest;
use App\Modules\Auth\Actions\RegisterWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\RegisterWithCredentialsData;
use App\Modules\Auth\Responses\LoginResponse;

class RegisterWithCredentialsController
{
    public function __invoke(
        RegisterWithCredentialsRequest $request,
        RegisterWithCredentialsAction $registerWithCredentialsAction,
    ): LoginResponse {
        return $registerWithCredentialsAction->execute(
            new RegisterWithCredentialsData(
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('password'),
            ),
        );
    }
}
