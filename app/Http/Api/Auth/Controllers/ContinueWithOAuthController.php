<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\ContinueWithOAuthRequest;
use App\Modules\Auth\Actions\ContinueWithOAuthAction;
use App\Modules\Auth\DataTransferObjects\ContinueWithOAuthData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\Responses\LoginResponse;
use Illuminate\Validation\ValidationException;

class ContinueWithOAuthController
{
    public function __invoke(
        ContinueWithOAuthRequest $request,
        ContinueWithOAuthAction $continueWithOAuthAction,
    ): LoginResponse {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            abort(404);
        }

        try {
            $loggedInUser = $continueWithOAuthAction->execute(
                ContinueWithOAuthData::fromToken(
                    provider: ProviderEnum::from($request->validated('provider')),
                    token: $request->getOAuthToken(),
                ),
            );
        } catch (UnableToContinueWithOAuthException) {
            throw ValidationException::withMessages([
                'token' => 'Unable to authenticate using this method. Please try again or use a different method.',
            ]);
        }

        return $loggedInUser;
    }
}
