<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\ForgotPasswordRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\ForgotPasswordAction;
use App\Modules\Auth\DataTransferObjects\ForgotPasswordData;

class ForgotPasswordController
{
    public function __invoke(
        ForgotPasswordRequest $request,
        ForgotPasswordAction $forgotPasswordAction,
    ): MessageResource {
        $forgotPasswordAction->execute(
            ForgotPasswordData::fromEmail($request->validated('email')),
        );

        return MessageResource::make('If an account exists, a password reset link has been sent.');
    }
}
