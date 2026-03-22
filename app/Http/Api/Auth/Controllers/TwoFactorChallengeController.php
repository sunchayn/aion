<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\TwoFactorChallengeRequest;
use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\TwoFactorChallengeAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\TwoFactorChallengeData;
use App\Modules\Auth\Responses\LoginResponse;
use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class TwoFactorChallengeController
{
    public function __invoke(
        TwoFactorChallengeRequest $request,
        TwoFactorChallengeAction $twoFactorChallengeAction,
        LoginAction $loginAction,
    ): LoginResponse|JsonResponse {
        $user = User::query()->findOrFail($request->getTokenUserId());

        $challengeSucceeded = $twoFactorChallengeAction->execute(
            TwoFactorChallengeData::fromUser(
                user: $user,
                code: $request->validated('code'),
                recoveryCode: $request->validated('recovery_code')
            ),
        );

        if (! $challengeSucceeded) {
            throw ValidationException::withMessages([
                'code' => 'The provided code was invalid.',
            ]);
        }

        return $loginAction->execute(new LoginData(
            user: $user,
            skipTwoFactor: true,
        ));
    }
}
