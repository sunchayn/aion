<?php

namespace Tests\App\Modules\Auth\Responses;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Generator;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(TokenLoginResponse::class)]
#[Group('authentication')]
class TokenLoginResponseUnitTest extends UnitTestCase
{
    public function test_it_is_instantiable_via_constructor(): void
    {
        // Arrange

        $user = \Mockery::mock(User::class);
        $authToken = 'auth-token-123';
        $refreshToken = 'refresh-token-456';
        $twoFactorToken = '2fa-token-789';

        // Act

        $response = new TokenLoginResponse(
            user: $user,
            authToken: $authToken,
            refreshToken: $refreshToken,
            twoFactorToken: $twoFactorToken,
            twoFactor: true,
        );

        // Assert

        $this->assertSame($user, $response->user);
        $this->assertSame($authToken, $response->authToken);
        $this->assertSame($refreshToken, $response->refreshToken);
        $this->assertSame($twoFactorToken, $response->twoFactorToken);
        $this->assertTrue($response->twoFactor);
    }

    public function test_it_is_instantiable_via_pending_two_factor_auth_factory(): void
    {
        // Arrange

        $user = \Mockery::mock(User::class);
        $twoFactorToken = '2fa-token-456';

        // Act

        $response = TokenLoginResponse::pendingTwoFactorAuth(
            user: $user,
            twoFactorToken: $twoFactorToken,
        );

        // Assert

        $this->assertSame($user, $response->user);
        $this->assertNull($response->authToken);
        $this->assertNull($response->refreshToken);
        $this->assertSame($twoFactorToken, $response->twoFactorToken);
        $this->assertTrue($response->twoFactor);
    }

    /**
     * @param  array<string, mixed>  $oauthAccountsData
     * @param  array<string, mixed>  $expected
     */
    #[DataProvider('conversionDataProvider')]
    public function test_it_transforms_to_json_response_successfully(
        ?string $authToken,
        ?string $refreshToken,
        ?string $twoFactorToken,
        bool $twoFactor,
        bool $emailVerified,
        array $oauthAccountsData,
        array $expected,
    ): void {
        // Arrange

        $userMock = \Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $userMock->shouldReceive('getAttribute')->with('email')->andReturn('test@example.com');
        $userMock->shouldReceive('getAttribute')->with('name')->andReturn('Test User');
        $userMock->shouldReceive('loadMissing')->with('oauthAccounts')->andReturnSelf();
        $userMock->shouldReceive('hasVerifiedEmail')->andReturn($emailVerified);

        $oauthAccounts = collect($oauthAccountsData)->map(fn ($oa) => (object) [
            'provider' => $oa['provider'],
            'provider_avatar' => $oa['avatar'],
        ]);
        $userMock->shouldReceive('getAttribute')->with('oauthAccounts')->andReturn($oauthAccounts);

        $actualResponse = new TokenLoginResponse(
            user: $userMock,
            authToken: $authToken,
            refreshToken: $refreshToken,
            twoFactorToken: $twoFactorToken,
            twoFactor: $twoFactor,
        );

        // Act

        $jsonResponse = $actualResponse->toResponse(\Mockery::mock(Request::class));

        // Assert

        $this->assertEquals(
            ['data' => $expected],
            $jsonResponse->getData(true),
        );
    }

    /**
     * @return Generator<string, array{authToken: string|null, refreshToken: string|null, twoFactorToken: string|null, twoFactor: bool, emailVerified: bool, oauthAccountsData: array<int, array<string, mixed>>, expected: array<string, mixed>}>
     */
    public static function conversionDataProvider(): Generator
    {
        yield 'minimal response with auth token' => [
            'authToken' => 'token-123',
            'refreshToken' => null,
            'twoFactorToken' => null,
            'twoFactor' => false,
            'emailVerified' => false,
            'oauthAccountsData' => [],
            'expected' => [
                'user_id' => 1,
                'two_factor' => false,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'email_verified' => false,
                'avatar_url' => null,
                'oauth_providers' => [],
                'auth_token' => 'token-123',
            ],
        ];

        yield 'full response with multiple oauth accounts' => [
            'authToken' => 'token-123',
            'refreshToken' => 'refresh-456',
            'twoFactorToken' => null,
            'twoFactor' => false,
            'emailVerified' => true,
            'oauthAccountsData' => [
                ['provider' => ProviderEnum::Google, 'avatar' => 'google-png'],
                ['provider' => ProviderEnum::GitHub, 'avatar' => 'github-jpg'],
            ],
            'expected' => [
                'user_id' => 1,
                'two_factor' => false,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'email_verified' => true,
                'avatar_url' => 'google-png',
                'oauth_providers' => ['google', 'github'],
                'auth_token' => 'token-123',
                'refresh_token' => 'refresh-456',
            ],
        ];

        yield '2fa pending response' => [
            'authToken' => null,
            'refreshToken' => null,
            'twoFactorToken' => '2fa-789',
            'twoFactor' => true,
            'emailVerified' => true,
            'oauthAccountsData' => [],
            'expected' => [
                'user_id' => 1,
                'two_factor' => true,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'email_verified' => true,
                'avatar_url' => null,
                'oauth_providers' => [],
                'two_factor_token' => '2fa-789',
            ],
        ];
    }
}
