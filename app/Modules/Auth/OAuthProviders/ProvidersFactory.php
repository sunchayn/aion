<?php

namespace App\Modules\Auth\OAuthProviders;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Providers\AppleOAuthProvider;
use App\Modules\Auth\OAuthProviders\Providers\GitHubOAuthProvider;
use App\Modules\Auth\OAuthProviders\Providers\GoogleOAuthProvider;

/** @final */
class ProvidersFactory
{
    public function make(ProviderEnum $provider): OAuthProviderBase
    {
        $this->ensureProviderIsInstalled($provider);

        return match ($provider) {
            ProviderEnum::Google => resolve(GoogleOAuthProvider::class),
            ProviderEnum::Apple => resolve(AppleOAuthProvider::class),
            ProviderEnum::GitHub => resolve(GitHubOAuthProvider::class),
        };
    }

    private function ensureProviderIsInstalled(ProviderEnum $provider): void
    {
        $driverClass = match ($provider) {
            ProviderEnum::Google => 'SocialiteProviders\Google\Provider',
            ProviderEnum::Apple => 'SocialiteProviders\Apple\Provider',
            ProviderEnum::GitHub => 'SocialiteProviders\GitHub\Provider',
        };

        if (! class_exists($driverClass)) {
            throw new \RuntimeException("OAuth provider [{$provider->value}] is not installed. Please install the [socialiteproviders/{$provider->value}] package.");
        }
    }
}
