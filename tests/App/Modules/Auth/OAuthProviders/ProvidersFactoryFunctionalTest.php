<?php

namespace Tests\App\Modules\Auth\OAuthProviders;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Providers\AppleOAuthProvider;
use App\Modules\Auth\OAuthProviders\Providers\GitHubOAuthProvider;
use App\Modules\Auth\OAuthProviders\Providers\GoogleOAuthProvider;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ProvidersFactory::class)]
#[Group('authentication')]
#[Group('oauth')]
class ProvidersFactoryFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.apple.oauth' => [
                'client_id' => 'com.example.app',
                'client_secret' => '',
                'redirect_url' => 'https://example.com/oauth/apple/callback',
                'team_id' => 'TEAM123456',
                'key_id' => 'KEY123456',
                'private_key' => '',
            ],
            'services.github.oauth' => [
                'client_id' => 'github-client-id',
                'client_secret' => 'github-client-secret',
                'redirect_url' => 'https://example.com/oauth/github/callback',
            ],
        ]);
    }

    #[DataProvider('factoryDataProvider')]
    public function test_it_makes_provider_instance(
        ProviderEnum $provider,
        string $expected,
    ): void {
        // Arrange

        match ($provider) {
            ProviderEnum::Google => $this->skipTestWhenGoogleIsMissing(),
            ProviderEnum::Apple => $this->skipTestWhenAppleIsMissing(),
            ProviderEnum::GitHub => $this->skipTestWhenGitHubIsMissing(),
            default => null,
        };

        $factory = new ProvidersFactory;

        // Act

        $actual = $factory->make($provider);

        // Assert

        $this->assertInstanceOf(
            $expected,
            $actual,
        );
    }

    public static function factoryDataProvider(): Generator
    {
        yield 'google' => [
            'provider' => ProviderEnum::Google,
            'expected' => GoogleOAuthProvider::class,
        ];

        yield 'apple' => [
            'provider' => ProviderEnum::Apple,
            'expected' => AppleOAuthProvider::class,
        ];

        yield 'github' => [
            'provider' => ProviderEnum::GitHub,
            'expected' => GitHubOAuthProvider::class,
        ];
    }
}
