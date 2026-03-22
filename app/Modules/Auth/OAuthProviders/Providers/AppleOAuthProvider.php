<?php

namespace App\Modules\Auth\OAuthProviders\Providers;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\OAuthProviderBase;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Apple\Provider as SocialiteProvider;
use SocialiteProviders\Manager\OAuth2\User as SocialiteOAuth2User;

class AppleOAuthProvider extends OAuthProviderBase
{
    public function __construct(
        #[Config('services.apple.oauth.client_id')]
        private readonly string $clientId,
        #[Config('services.apple.oauth.client_secret')]
        private readonly string $clientSecret,
        #[Config('services.apple.oauth.redirect_url')]
        private readonly string $redirectUrl,
        #[Config('services.apple.oauth.team_id')]
        private readonly string $teamId,
        #[Config('services.apple.oauth.key_id')]
        private readonly string $keyId,
        #[Config('services.apple.oauth.private_key')]
        private readonly string $privateKey,
    ) {
        parent::__construct();
    }

    public function buildSocialite(): AbstractProvider
    {
        return Socialite::buildProvider(SocialiteProvider::class, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect' => $this->redirectUrl,
            'team_id' => $this->teamId,
            'key_id' => $this->keyId,
            'private_key' => $this->privateKey,
        ]);
    }

    public function getName(): ProviderEnum
    {
        return ProviderEnum::Apple;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestParameters(): array
    {
        return [
            'scope' => 'name email',
            'response_type' => 'code id_token',
        ];
    }

    /**
     * Apple sends the user's name only on the very first login via a POST body `user` field.
     * The SocialiteProviders\Apple driver parses this and populates the raw data automatically.
     * Subsequent logins will not include the name fields.
     */
    public function getUserNameProps(SocialiteUser $user): array
    {
        $rawData = $user->getRaw();

        return [
            'first_name' => $rawData['name']['firstName'] ?? $user->getId(),
            'last_name' => $rawData['name']['lastName'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function performUserFetch(OAuthToken $token, array $state = []): ?SocialiteUser
    {
        if ($token->isAccessToken()) {
            $user = $this->socialite->stateless()->userFromToken($token->value);

            if ($user instanceof SocialiteOAuth2User) {
                $user->setAccessTokenResponseBody(['access_token' => $user->token]);
            }

            return $user;
        }

        // Authorization code flow: exchange code for tokens, then decode the id_token.
        $response = $this->socialite->stateless()->getAccessTokenResponse($token->value);
        $accessToken = $response['id_token'];

        $user = $this->socialite->stateless()->userFromToken($accessToken);

        if ($user instanceof SocialiteOAuth2User) {
            $user->setAccessTokenResponseBody($response);
        }

        return $user
            ->setToken($accessToken)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    public function revokeAccess(string $token): void
    {
        /** @var SocialiteProvider $provider */
        $provider = $this->socialite;
        $provider->revokeToken($token);
    }
}
