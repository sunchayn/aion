<?php

namespace Tests\App\Modules\Auth\OAuthProviders\Providers;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Providers\AppleOAuthProvider;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Generator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SocialiteProviders\Apple\Provider as SocialiteProvider;
use SocialiteProviders\Manager\OAuth2\User as SocialiteOAuth2User;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(AppleOAuthProvider::class)]
#[Group('authentication')]
#[Group('oauth')]
class AppleOAuthProviderFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenAppleIsMissing();

        config([
            'services.apple.oauth' => [
                'client_id' => 'com.example.app',
                'client_secret' => '',
                'redirect_url' => 'https://example.com/oauth/apple/callback',
                'team_id' => 'TEAM123456',
                'key_id' => 'KEY123456',
                'private_key' => '',
            ],
        ]);
    }

    public function test_it_returns_the_correct_name(): void
    {
        // Arrange

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->getName();

        // Assert

        $this->assertSame(ProviderEnum::Apple, $actual);
    }

    public function test_it_returns_the_correct_request_parameters(): void
    {
        // Arrange

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->requestParameters();

        // Assert

        $this->assertSame(['scope' => 'name email', 'response_type' => 'code id_token'], $actual);
    }

    #[DataProvider('userNamePropsDataProvider')]
    public function test_it_gets_user_name_props(array $rawData, string $id, array $expected): void
    {
        // Arrange

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        $user = new SocialiteUser;
        $user->map(['id' => $id]);
        $user->setRaw($rawData);

        // Act

        $actual = $provider->getUserNameProps($user);

        // Assert

        $this->assertSame($expected, $actual);
    }

    public static function userNamePropsDataProvider(): Generator
    {
        yield 'name present on first login' => [
            'rawData' => ['name' => ['firstName' => 'John', 'lastName' => 'Doe']],
            'id' => '000.APPLE_USER_ID',
            'expected' => ['first_name' => 'John', 'last_name' => 'Doe'],
        ];

        yield 'only first name' => [
            'rawData' => ['name' => ['firstName' => 'John']],
            'id' => '000.APPLE_USER_ID',
            'expected' => ['first_name' => 'John', 'last_name' => null],
        ];

        yield 'name absent on subsequent logins — falls back to id' => [
            'rawData' => [],
            'id' => '000.APPLE_USER_ID',
            'expected' => ['first_name' => '000.APPLE_USER_ID', 'last_name' => null],
        ];
    }

    public function test_it_builds_socialite_provider(): void
    {
        // Arrange

        $clientId = fake()->uuid();
        $redirectUrl = fake()->url();

        config([
            'services.apple.oauth.client_id' => $clientId,
            'services.apple.oauth.redirect_url' => $redirectUrl,
        ]);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $socialite = $provider->buildSocialite();

        // Assert

        $this->assertInstanceOf(SocialiteProvider::class, $socialite);
        $this->assertEquals($clientId, invade($socialite)->clientId);
        $this->assertEquals($redirectUrl, invade($socialite)->redirectUrl);
    }

    public function test_it_performs_user_fetch_with_authorization_code(): void
    {
        // Arrange

        $token = OAuthToken::authorizationToken('test-auth-code');

        $idToken = 'test-id-token';
        $response = ['id_token' => $idToken, 'refresh_token' => 'rt', 'expires_in' => 3600];
        $socialiteUser = new SocialiteOAuth2User;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();

        $socialiteMock
            ->expects('getAccessTokenResponse')
            ->with('test-auth-code')
            ->andReturn($response);

        $socialiteMock
            ->expects('userFromToken')
            ->with($idToken)
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($idToken, $actual->token);
        $this->assertSame('rt', $actual->refreshToken);
        $this->assertSame(3600, $actual->expiresIn);
    }

    public function test_it_performs_user_fetch_with_access_token(): void
    {
        // Arrange

        $tokenValue = 'test-access-token';
        $token = OAuthToken::accessToken($tokenValue);
        $socialiteUser = new SocialiteOAuth2User;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();

        $socialiteMock
            ->expects('userFromToken')
            ->with($tokenValue)
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($socialiteUser, $actual);
    }

    public function test_it_performs_user_fetch_with_authorization_code_and_standard_user(): void
    {
        // Arrange

        $token = OAuthToken::authorizationToken('test-auth-code');

        $idToken = 'test-id-token';
        $response = ['id_token' => $idToken, 'refresh_token' => 'rt', 'expires_in' => 3600];
        $socialiteUser = new SocialiteUser;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();

        $socialiteMock
            ->expects('getAccessTokenResponse')
            ->with('test-auth-code')
            ->andReturn($response);

        $socialiteMock
            ->expects('userFromToken')
            ->with($idToken)
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($idToken, $actual->token);
        $this->assertSame('rt', $actual->refreshToken);
        $this->assertSame(3600, $actual->expiresIn);
    }

    public function test_it_performs_user_fetch_with_access_token_and_standard_user(): void
    {
        // Arrange

        $tokenValue = 'test-access-token';
        $token = OAuthToken::accessToken($tokenValue);
        $socialiteUser = new SocialiteUser;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();

        $socialiteMock
            ->expects('userFromToken')
            ->with($tokenValue)
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($socialiteUser, $actual);
    }

    public function test_it_revokes_access(): void
    {
        // Arrange

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->expects('revokeToken')->with('test-token')->once();

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var AppleOAuthProvider $provider */
        $provider = resolve(AppleOAuthProvider::class);

        // Act

        $provider->revokeAccess('test-token');

        // Assert

        $this->assertTrue(true);
    }
}
