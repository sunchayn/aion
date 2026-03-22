<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\LoginWithCredentialsData;
use App\Modules\Auth\Exceptions\UnableToLoginException;
use App\Modules\Auth\Responses\LoginResponse;
use App\Modules\Shared\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

/** @final */
class LoginWithCredentialsAction
{
    public function __construct(
        private readonly LoginAction $loginAction,
        private readonly Hasher $hasher,
    ) {}

    /**
     * @throws ValidationException
     * @throws UnableToLoginException
     */
    public function execute(LoginWithCredentialsData $data): LoginResponse
    {
        if (! $data->user instanceof User) {
            throw UnableToLoginException::becauseUserCannotBeFound();
        }

        if (! $this->hasher->check((string) $data->password, $data->user->getAuthPassword())) {
            throw UnableToLoginException::becauseOfInvalidCredentials();
        }

        return $this->loginAction->execute(
            data: new LoginData(
                user: $data->user,
                guard: $data->guard,
                remember: $data->remember,
            ),
        );
    }
}
