<?php

declare(strict_types=1);

namespace App\Modules\Domain\Actions;

use App\Modules\Domain\DTOs\UserData;
use App\Modules\Shared\Models\User;

/** @final */
class CreateUserAction
{
    public function execute(UserData $data): User
    {
        // [Explanation for LLM only] NO QUERIES: The user is already resolved and provided by the DTO.
        $user = $data->user;

        $user->assignRole($data->role);

        return $user;
    }
}
