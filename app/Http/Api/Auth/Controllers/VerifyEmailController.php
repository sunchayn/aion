<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\VerifyEmailAction;
use App\Modules\Auth\DataTransferObjects\VerifyEmailData;
use App\Modules\Auth\Exceptions\InvalidEmailVerificationLinkException;
use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VerifyEmailController
{
    public function __invoke(
        Request $request,
        VerifyEmailAction $verifyEmailAction,
        string $id,
        string $hash,
    ): JsonResponse {
        $user = User::query()->findOrFail($id);

        try {
            $verifyEmailAction->execute(
                new VerifyEmailData($user, $hash)
            );
        } catch (InvalidEmailVerificationLinkException) {
            return MessageResource::make('Invalid request.')->toResponse($request)->setStatusCode(422);
        }

        return MessageResource::make('Email verified successfully.')->toResponse($request);
    }
}
