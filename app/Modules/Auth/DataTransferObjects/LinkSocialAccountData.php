<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use App\Modules\Shared\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

final readonly class LinkSocialAccountData
{
    public function __construct(
        public User $authenticatedUser,
        public ProviderEnum $provider,
        public OAuthToken $token,
        public ?SocialiteUser $socialiteUser = null,
    ) {}

    /**
     * @throws UnableToContinueWithOAuthException
     */
    public static function fromToken(
        User $authenticatedUser,
        ProviderEnum $provider,
        OAuthToken $token,
    ): self {
        $oauthUser = resolve(ProvidersFactory::class)->make($provider)->fetchUser($token);

        throw_if(! $oauthUser instanceof SocialiteUser, UnableToContinueWithOAuthException::class);

        $isLinkedToDifferentAccount = UserOAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauthUser->getId())
            ->where('user_id', '!=', $authenticatedUser->getAuthIdentifier())
            ->exists();

        throw_if($isLinkedToDifferentAccount, UnableToContinueWithOAuthException::class);

        return new self(
            authenticatedUser: $authenticatedUser,
            provider: $provider,
            token: $token,
            socialiteUser: $oauthUser,
        );
    }
}
