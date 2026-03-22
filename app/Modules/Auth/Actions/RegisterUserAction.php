<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Shared\Models\User;
use App\Modules\Users\Actions\CreateUserAction;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;

/** @final */
class RegisterUserAction
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(CreateUserData $data): User
    {
        $user = $this->createUserAction->execute($data);

        $this->dispatcher->dispatch(
            new Registered($user),
        );

        return $user;
    }
}
