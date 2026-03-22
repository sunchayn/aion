<?php

namespace App\Modules\Auth\OAuthProviders;

use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\AbstractProvider as AbstractSocialiteProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Manager\OAuth2\User as SocialiteOAuth2User;

abstract class OAuthProviderBase
{
    protected AbstractSocialiteProvider $socialite;

    public function __construct()
    {
        $this->socialite = $this->buildSocialite();
    }

    abstract public function buildSocialite(): AbstractSocialiteProvider;

    /*
     * Getters.
     */
    public function socialite(): AbstractSocialiteProvider
    {
        return $this->socialite;
    }

    abstract public function getName(): ProviderEnum;

    /**
     * @return array<string, mixed>
     */
    abstract public function requestParameters(): array;

    /**
     * Get the user's name properties.
     *
     * @return array{first_name: string, last_name: ?string}
     */
    abstract public function getUserNameProps(SocialiteUser $user): array;

    /*
     * Requests.
     */

    /**
     * Redirect to the provider.
     */
    public function redirectToProvider(OperationTypeEnum $operationType): RedirectResponse
    {
        return $this->socialite
            ->stateless()
            ->with([
                'state' => base64_encode(
                    (string) json_encode([
                        'operation_type' => $operationType->value,
                    ]),
                ),
                ...$this->requestParameters(),
            ])
            ->redirect();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function fetchUser(OAuthToken $token, array $state = []): ?SocialiteUser
    {
        /** @var ?SocialiteUser $socialiteUser */
        $socialiteUser = rescue(
            fn (): ?SocialiteUser => $this->performUserFetch($token, $state),
            report: true,
        );

        if ($socialiteUser === null) {
            return null;
        }

        if (! $socialiteUser->id || ! $socialiteUser->email) {
            // Even though SocialiteUser->email is annotated as string, it should be used as a source of truth
            // Some providers like Apple might return no email address if the user choose to.
            return null;
        }

        return $socialiteUser;
    }

    /**
     * Execute the user fetching request to the provider.
     *
     * @param  array<string, mixed>  $state
     *
     * @throws GuzzleException
     */
    abstract public function performUserFetch(OAuthToken $token, array $state = []): ?SocialiteUser;

    /**
     * @throws CannotPerformOauthOperationException
     */
    public function revokeAccess(string $token): void
    {
        throw CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRevocation($this->getName()->value);
    }

    /**
     * @throws CannotPerformOauthOperationException
     */
    public function refreshToken(string $token): ?string
    {
        throw CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRefresh($this->getName()->value);
    }

    /**
     * @return array{access_token?: string, refresh_token?: ?string}
     */
    public function getTokens(SocialiteUser|SocialiteOAuth2User $user): array
    {
        return [
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken,
        ];
    }
}
