<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\ConfirmTwoFactorRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\VerifyTwoFactorAuthCodeAction;
use App\Modules\Auth\DataTransferObjects\VerifyTwoFactorAuthCodeData;
use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ConfirmTwoFactorController
{
    public function __invoke(
        ConfirmTwoFactorRequest $request,
        VerifyTwoFactorAuthCodeAction $verifyTwoFactorAuthCodeAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $verified = rescue(
            fn (): bool => $verifyTwoFactorAuthCodeAction->execute(
                VerifyTwoFactorAuthCodeData::fromUser(
                    $user,
                    $request->validated('code'),
                ),
            ),
            rescue: false,
            report: false,
        );

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => ['The provided code was invalid.'],
            ]);
        }

        return MessageResource::make('2FA has been confirmed.')->toResponse($request);
    }
}
