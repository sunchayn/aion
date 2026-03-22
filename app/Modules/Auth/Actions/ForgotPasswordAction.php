<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\ForgotPasswordData;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Password;

/** @final */
class ForgotPasswordAction
{
    public function execute(ForgotPasswordData $data): void
    {
        if (! $data->user instanceof User) {
            // When no user is matching the email we just discard the rest request.
            // The requester shouldn't be informed about this (a.k.a sending 404).
            return;
        }

        $token = Password::createToken($data->user);

        $data->user->notify(new ResetPasswordNotification($token));
    }
}
