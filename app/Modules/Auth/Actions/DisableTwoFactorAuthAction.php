<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Shared\Models\User;

/** @final */
class DisableTwoFactorAuthAction
{
    public function execute(User $user): void
    {
        $user->twoFactorAuth()->delete();
    }
}
