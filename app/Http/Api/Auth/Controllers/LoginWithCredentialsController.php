<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\LoginWithCredentialsRequest;
use App\Modules\Auth\Actions\LoginWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\LoginWithCredentialsData;
use App\Modules\Auth\Exceptions\UnableToLoginException;
use App\Modules\Auth\Responses\LoginResponse;
use Illuminate\Validation\ValidationException;

class LoginWithCredentialsController
{
    public function __invoke(LoginWithCredentialsRequest $request, LoginWithCredentialsAction $loginWithCredentialsAction): LoginResponse
    {
        try {
            $loggedInUser = $loginWithCredentialsAction->execute(
                LoginWithCredentialsData::fromEmail(
                    email: $request->validated('email'),
                    password: $request->validated('password'),
                    remember: $request->boolean('remember'),
                ),
            );
        } catch (UnableToLoginException) {
            throw ValidationException::withMessages([
                'email' => ['Unable to log in with the provided credentials.'],
            ]);
        }

        return $loggedInUser;
    }
}
