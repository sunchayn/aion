<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LinkSocialAccountData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\Models\UserOAuthAccount;
use Laravel\Socialite\Two\User;

/** @final */
class LinkSocialAccountAction
{
    public function execute(LinkSocialAccountData $data): void
    {
        $oauthUser = $data->socialiteUser;

        throw_if(! $oauthUser instanceof User, UnableToContinueWithOAuthException::class);

        UserOAuthAccount::query()->updateOrCreate(
            [
                'user_id' => $data->authenticatedUser->getAuthIdentifier(),
                'provider' => $data->provider,
            ],
            [
                'provider_user_id' => $oauthUser->getId(),
                'provider_avatar' => $oauthUser->getAvatar(),
                'access_token' => $oauthUser->token,
                'refresh_token' => $oauthUser->refreshToken,
                'expires_at' => $oauthUser->expiresIn ? now()->addSeconds($oauthUser->expiresIn) : null,
            ],
        );
    }
}
