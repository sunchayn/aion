<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Shared\Models\User;

/** @final */
class ResendEmailVerificationAction
{
    public function execute(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }
}
