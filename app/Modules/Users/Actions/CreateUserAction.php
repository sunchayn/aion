<?php

namespace App\Modules\Users\Actions;

use App\Modules\Shared\Models\User;
use App\Modules\Users\DataTransferObjects\CreateUserData;

/** @final */
class CreateUserAction
{
    public function execute(CreateUserData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
    }
}
