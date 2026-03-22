<?php

namespace Tests\App\Modules\Auth\OAuthProviders\Providers;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use App\Modules\Auth\OAuthProviders\Providers\GoogleOAuthProvider;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Generator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SocialiteProviders\Google\Provider as SocialiteProvider;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(GoogleOAuthProvider::class)]
#[Group('authentication')]
#[Group('oauth')]
class GoogleOAuthProviderFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenGoogleIsMissing();
    }

    public function test_it_returns_the_correct_name(): void
    {
        // Arrange

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        // Act

        $actual = $provider->getName();

        // Assert

        $this->assertSame(ProviderEnum::Google, $actual);
    }

    public function test_it_returns_the_correct_request_parameters(): void
    {
        // Arrange

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        // Act

        $actual = $provider->requestParameters();

        // Assert

        $this->assertSame(['access_type' => 'offline'], $actual);
    }

    #[DataProvider('userNamePropsDataProvider')]
    public function test_it_gets_user_name_props(array $rawData, array $expected): void
    {
        // Arrange

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        $user = new SocialiteUser;
        $user->setRaw($rawData);

        // Act

        $actual = $provider->getUserNameProps($user);

        // Assert

        $this->assertSame($expected, $actual);
    }

    /*
    * Data Providers.
    */
    public static function userNamePropsDataProvider(): Generator
    {
        yield 'given and family names' => [
            'rawData' => [
                'given_name' => 'John',
                'family_name' => 'Doe',
            ],
            'expected' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ];

        yield 'only given name' => [
            'rawData' => [
                'given_name' => 'John',
            ],
            'expected' => [
                'first_name' => 'John',
                'last_name' => null,
            ],
        ];

        yield 'fallback to name' => [
            'rawData' => [
                'name' => 'John',
            ],
            'expected' => [
                'first_name' => 'John',
                'last_name' => null,
            ],
        ];
    }

    public function test_it_throws_exception_when_name_is_missing(): void
    {
        // Arrange

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        $user = new SocialiteUser;
        $user->setRaw([]);

        // Anticipate

        $this->expectExceptionObject(CannotPerformOauthOperationException::becauseUnableToFindNameInRemoteUser(ProviderEnum::Google->value));

        // Act

        $provider->getUserNameProps($user);
    }

    public function test_it_builds_socialite_provider(): void
    {
        // Arrange

        config([
            'services.google.oauth' => [
                'client_id' => $clientId = fake()->uuid(),
                'client_secret' => $clientSecret = fake()->uuid(),
                'redirect_url' => $redirectUrl = fake()->url(),
            ],
        ]);

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        // Act

        $socialite = $provider->buildSocialite();

        // Assert

        $this->assertInstanceOf(SocialiteProvider::class, $socialite);

        $this->assertEquals(
            $clientId,
            invade($socialite)->clientId,
        );

        $this->assertEquals(
            $clientSecret,
            invade($socialite)->clientSecret,
        );

        $this->assertEquals(
            $redirectUrl,
            invade($socialite)->redirectUrl,
        );
    }

    public function test_it_performs_user_fetch_with_access_token(): void
    {
        // Arrange

        $tokenValue = 'test-token';
        $token = OAuthToken::accessToken($tokenValue);
        $socialiteUser = new SocialiteUser;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();

        $socialiteMock
            ->expects('userFromToken')
            ->with($tokenValue)
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($socialiteUser, $actual);
    }

    public function test_it_performs_user_fetch_with_authorization_token(): void
    {
        // Arrange

        $tokenValue = 'test-code';
        $token = OAuthToken::authorizationToken($tokenValue);
        $socialiteUser = new SocialiteUser;

        $socialiteMock = $this->mock(SocialiteProvider::class);
        $socialiteMock->allows('stateless')->andReturnSelf();
        $socialiteMock->allows('with')->with(['code' => $tokenValue])->andReturnSelf();

        $socialiteMock
            ->expects('user')
            ->andReturn($socialiteUser);

        Socialite::expects('buildProvider')->andReturn($socialiteMock);

        /** @var GoogleOAuthProvider $provider */
        $provider = resolve(GoogleOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($socialiteUser, $actual);
    }
}
