<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\RefreshTokenRequest;
use App\Modules\Auth\Actions\RefreshTokenAction;
use App\Modules\Auth\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Responses\LoginResponse;
use Illuminate\Http\JsonResponse;

final class RefreshTokenController
{
    public function __invoke(
        RefreshTokenRequest $request,
        RefreshTokenAction $refreshTokenAction,
    ): LoginResponse|JsonResponse {
        try {
            $loggedInUser = $refreshTokenAction->execute(
                $request->validated('refresh_token'),
            );
        } catch (InvalidRefreshTokenException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        return $loggedInUser;
    }
}
