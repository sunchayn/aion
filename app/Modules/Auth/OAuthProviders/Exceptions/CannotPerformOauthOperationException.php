<?php

namespace App\Modules\Auth\OAuthProviders\Exceptions;

use Illuminate\Http\Client\HttpClientException;

class CannotPerformOauthOperationException extends HttpClientException
{
    public static function becauseProviderDoesntSupportTokenRefresh(string $provider): self
    {
        return new self(
            message: "Unsupported feature for this oauth provider [{$provider}]. Feature requested: <Refresh Auth token>",
        );
    }

    public static function becauseProviderDoesntSupportTokenRevocation(string $provider): self
    {
        return new self(
            message: "Unsupported feature for this oauth provider [{$provider}]. Feature requested: <Revoke Auth token>",
        );
    }

    public static function becauseUnableToFindNameInRemoteUser(string $provider): self
    {
        return new self(
            message: "Unable to find the name from [{$provider}]'s remote user.",
        );
    }

    public static function becauseNotImplemented(string $provider, string $feature): self
    {
        return new self(
            message: "Feature <$feature> is not implemented for [{$provider}].",
        );
    }
}
