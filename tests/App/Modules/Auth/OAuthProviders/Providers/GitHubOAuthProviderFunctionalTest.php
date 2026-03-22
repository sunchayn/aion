<?php

namespace Tests\App\Modules\Auth\OAuthProviders\Providers;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use App\Modules\Auth\OAuthProviders\Providers\GitHubOAuthProvider;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Generator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SocialiteProviders\GitHub\Provider as SocialiteProvider;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(GitHubOAuthProvider::class)]
#[Group('authentication')]
#[Group('oauth')]
class GitHubOAuthProviderFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenGitHubIsMissing();
    }

    public function test_it_returns_the_correct_name(): void
    {
        // Arrange

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        // Act

        $actual = $provider->getName();

        // Assert

        $this->assertSame(ProviderEnum::GitHub, $actual);
    }

    public function test_it_returns_the_correct_request_parameters(): void
    {
        // Arrange

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        // Act

        $actual = $provider->requestParameters();

        // Assert

        $this->assertSame(['scope' => 'user:email'], $actual);
    }

    #[DataProvider('userNamePropsDataProvider')]
    public function test_it_gets_user_name_props(string $name, array $expected): void
    {
        // Arrange

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        $user = new SocialiteUser;
        $user->map(['name' => $name]);

        // Act

        $actual = $provider->getUserNameProps($user);

        // Assert

        $this->assertSame($expected, $actual);
    }

    public static function userNamePropsDataProvider(): Generator
    {
        yield 'full name with first and last' => [
            'name' => 'John Doe',
            'expected' => ['first_name' => 'John', 'last_name' => 'Doe'],
        ];

        yield 'single word name' => [
            'name' => 'John',
            'expected' => ['first_name' => 'John', 'last_name' => null],
        ];

        yield 'name with multiple spaces' => [
            'name' => 'John Middle Doe',
            'expected' => ['first_name' => 'John', 'last_name' => 'Middle Doe'],
        ];
    }

    public function test_it_throws_exception_when_name_is_missing(): void
    {
        // Arrange

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        $user = new SocialiteUser;
        $user->map(['name' => null]);

        // Anticipate

        $this->expectExceptionObject(
            CannotPerformOauthOperationException::becauseUnableToFindNameInRemoteUser(ProviderEnum::GitHub->value),
        );

        // Act

        $provider->getUserNameProps($user);
    }

    public function test_it_builds_socialite_provider(): void
    {
        // Arrange

        config([
            'services.github.oauth' => [
                'client_id' => $clientId = fake()->uuid(),
                'client_secret' => $clientSecret = fake()->uuid(),
                'redirect_url' => $redirectUrl = fake()->url(),
            ],
        ]);

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        // Act

        $socialite = $provider->buildSocialite();

        // Assert

        $this->assertInstanceOf(SocialiteProvider::class, $socialite);

        $this->assertEquals($clientId, invade($socialite)->clientId);
        $this->assertEquals($clientSecret, invade($socialite)->clientSecret);
        $this->assertEquals($redirectUrl, invade($socialite)->redirectUrl);
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

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

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

        /** @var GitHubOAuthProvider $provider */
        $provider = resolve(GitHubOAuthProvider::class);

        // Act

        $actual = $provider->performUserFetch($token);

        // Assert

        $this->assertSame($socialiteUser, $actual);
    }
}
