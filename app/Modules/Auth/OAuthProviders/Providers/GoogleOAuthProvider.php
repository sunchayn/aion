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
use SocialiteProviders\Google\Provider as SocialiteProvider;

class GoogleOAuthProvider extends OAuthProviderBase
{
    public function __construct(
        #[Config('services.google.oauth.client_id')]
        private readonly string $clientId,
        #[Config('services.google.oauth.client_secret')]
        private readonly string $clientSecret,
        #[Config('services.google.oauth.redirect_url')]
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
        return ProviderEnum::Google;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestParameters(): array
    {
        return [
            'access_type' => 'offline',
        ];
    }

    /**
     * @throws CannotPerformOauthOperationException
     */
    public function getUserNameProps(SocialiteUser $user): array
    {
        $rawData = $user->getRaw();

        if (! isset($rawData['given_name']) && ! isset($rawData['name'])) {
            throw CannotPerformOauthOperationException::becauseUnableToFindNameInRemoteUser($this->getName()->value);
        }

        return [
            'first_name' => $rawData['given_name'] ?? $rawData['name'],
            'last_name' => $rawData['family_name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     *
     * @throws CannotPerformOauthOperationException
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
