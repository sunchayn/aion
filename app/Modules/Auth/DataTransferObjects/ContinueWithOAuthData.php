<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use App\Modules\Shared\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

final readonly class ContinueWithOAuthData
{
    public function __construct(
        public ProviderEnum $provider,
        public ?User $user,
        public ?SocialiteUser $socialiteUser,
        public bool $alreadyLinked,
        public bool $remember = true,
    ) {}

    public static function empty(ProviderEnum $provider, bool $remember = true): self
    {
        return new self(
            provider: $provider,
            user: null,
            socialiteUser: null,
            alreadyLinked: false,
            remember: $remember,
        );
    }

    public static function fromToken(
        ProviderEnum $provider,
        OAuthToken $token,
        bool $remember = true,
    ): self {
        $oauthUser = resolve(ProvidersFactory::class)->make($provider)->fetchUser($token);

        if ($oauthUser === null) {
            return self::empty($provider, $remember);
        }

        $user = UserOAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauthUser->getId())
            ->first()
            ?->user;

        $alreadyLinked = $user !== null;

        // If the user is not linked already, let's first try to find if the email is already registered with.
        // If so, we will link the accounts directly.
        $user ??= User::query()->where('email', $oauthUser->getEmail())->first();

        return new self(
            provider: $provider,
            user: $user,
            socialiteUser: $oauthUser,
            alreadyLinked: $alreadyLinked,
            remember: $remember,
        );
    }
}
