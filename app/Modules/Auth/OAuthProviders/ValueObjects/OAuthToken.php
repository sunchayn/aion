<?php

namespace App\Modules\Auth\OAuthProviders\ValueObjects;

use App\Modules\Auth\OAuthProviders\Enum\OAuthTokenTypeEnum;

final readonly class OAuthToken
{
    private function __construct(
        public string $value,
        public OAuthTokenTypeEnum $type,
    ) {}

    /**
     * Create an authorization code token.
     *
     * An authorization code is the short-lived code returned by the provider's redirect (code flow).
     * It is used once to exchange for a long-lived access token at the provider's token endpoint.
     */
    public static function authorizationToken(string $value): self
    {
        return new self($value, OAuthTokenTypeEnum::AuthorizationToken);
    }

    /**
     * Create an access token.
     *
     * An access token is the persistent or semipersistent token obtained from the
     * OAuth provider's token endpoint (after exchanging the authorization code).
     * It is used to make API requests on behalf of the user to the provider.
     */
    public static function accessToken(string $value): self
    {
        return new self($value, OAuthTokenTypeEnum::AccessToken);
    }

    /**
     * @phpstan-assert-if-true OAuthTokenTypeEnum::AccessToken $this->type
     */
    public function isAccessToken(): bool
    {
        return $this->type === OAuthTokenTypeEnum::AccessToken;
    }

    /**
     * @phpstan-assert-if-true OAuthTokenTypeEnum::AuthorizationToken $this->type
     */
    public function isAuthorizationCode(): bool
    {
        return $this->type === OAuthTokenTypeEnum::AuthorizationToken;
    }
}
