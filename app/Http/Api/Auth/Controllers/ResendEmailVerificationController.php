<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\ResendEmailVerificationAction;
use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResendEmailVerificationController
{
    public function __invoke(
        Request $request,
        ResendEmailVerificationAction $resendEmailVerificationAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return MessageResource::make('Email is already verified.')->toResponse($request);
        }

        if ($resendEmailVerificationAction->execute($user)) {
            return MessageResource::make('Verification link sent.')->toResponse($request);
        }

        return MessageResource::make('Unable to send verification link.')->toResponse($request)->setStatusCode(500);
    }
}
