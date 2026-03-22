<?php

namespace App\Modules\Auth\OAuthProviders\Providers;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use App\Modules\Auth\OAuthProviders\OAuthProviderBase;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Illuminate\Container\Attributes\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\GitHub\Provider as SocialiteProvider;

class GitHubOAuthProvider extends OAuthProviderBase
{
    public function __construct(
        #[Config('services.github.oauth.client_id')]
        private readonly string $clientId,
        #[Config('services.github.oauth.client_secret')]
        private readonly string $clientSecret,
        #[Config('services.github.oauth.redirect_url')]
        private readonly string $redirectUrl,
    ) {
        parent::__construct();
    }

    public function buildSocialite(): AbstractProvider
    {
        return Socialite::buildProvider(SocialiteProvider::class, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect' => $this->redirectUrl,
        ]);
    }

    public function getName(): ProviderEnum
    {
        return ProviderEnum::GitHub;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestParameters(): array
    {
        return [
            'scope' => 'user:email',
        ];
    }

    /**
     * @throws CannotPerformOauthOperationException
     */
    public function getUserNameProps(SocialiteUser $user): array
    {
        $name = $user->getName();

        if (! $name) {
            throw CannotPerformOauthOperationException::becauseUnableToFindNameInRemoteUser($this->getName()->value);
        }

        $parts = explode(' ', $name, 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function performUserFetch(OAuthToken $token, array $state = []): ?SocialiteUser
    {
        if ($token->isAccessToken()) {
            return $this->socialite->stateless()->userFromToken($token->value);
        }

        /** @var SocialiteUser $user */
        $user = $this->socialite->stateless()->with(['code' => $token->value])->user();

        return $user;
    }
}
