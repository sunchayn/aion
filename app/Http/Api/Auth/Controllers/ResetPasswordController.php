<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\ResetPasswordRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\ResetPasswordAction;
use App\Modules\Auth\DataTransferObjects\ResetPasswordData;
use Illuminate\Validation\ValidationException;

class ResetPasswordController
{
    public function __invoke(
        ResetPasswordRequest $request,
        ResetPasswordAction $resetPasswordAction,
    ): MessageResource {
        $status = $resetPasswordAction->execute(
            new ResetPasswordData(
                $request->validated('email'),
                $request->validated('password'),
                $request->validated('token'),
            ),
        );

        if (! $status) {
            throw ValidationException::withMessages([
                'email' => ['The password reset token is invalid.'],
            ]);
        }

        return MessageResource::make('Your password has been reset.');
    }
}
