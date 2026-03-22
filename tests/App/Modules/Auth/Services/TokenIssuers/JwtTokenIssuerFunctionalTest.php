<?php

namespace Tests\App\Modules\Auth\Services\TokenIssuers;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Auth\Services\TokenIssuers\JwtTokenIssuer;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Mockers\MocksAuthGuards;
use Tests\Support\TestCases\FunctionalTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

#[CoversClass(JwtTokenIssuer::class)]
#[CoversClass(IssuedTokens::class)]
#[Group('authentication')]
class JwtTokenIssuerFunctionalTest extends FunctionalTestCase
{
    private const int REFRESH_TOKEN_TTL = 20160; // <- 14 days

    use MocksAuthGuards;

    private JwtTokenIssuer $jwtTokenIssuer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenJwtIsNotAvailable();

        ['guardName' => $guardName] = $this->makeDummyStatelessGuard();

        $this->jwtTokenIssuer = resolve(JwtTokenIssuer::class);
        $this->jwtTokenIssuer->useGuard($guardName);

        Config::set('auth.jwt.refresh_token_ttl', self::REFRESH_TOKEN_TTL);
    }

    public function test_it_issues_tokens(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $tokens = $this->jwtTokenIssuer->issue($user);

        // Assert

        $this->assertTokensAreValid($tokens);
    }

    public function test_it_refreshes_token_successfully(): void
    {
        // Arrange

        $user = User::factory()->create();

        $originalTokens = $this->jwtTokenIssuer->issue($user);

        // Act

        $newTokens = $this->jwtTokenIssuer->refresh($originalTokens->refreshToken);

        // Assert

        $this->assertNotEquals($originalTokens->authToken, $newTokens->authToken);
        $this->assertNotEquals($originalTokens->refreshToken, $newTokens->refreshToken);

        $this->assertTokensAreValid($newTokens);
    }

    public function test_it_returns_null_when_refreshing_with_expired_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $tokens = $this->jwtTokenIssuer->issue($user);

        $this->travel(self::REFRESH_TOKEN_TTL + fake()->randomDigitNotZero())->days();

        // Act

        $result = $this->jwtTokenIssuer->refresh($tokens->refreshToken);

        // Assert

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_refreshing_with_invalid_user(): void
    {
        // Arrange

        $user = new User(['id' => 9999999]); // <- Non-existent user

        $token = JWTAuth::claims(['type' => 'refresh'])->fromUser($user);

        // Act

        $result = $this->jwtTokenIssuer->refresh($token);

        // Assert

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_refreshing_with_access_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $tokens = $this->jwtTokenIssuer->issue($user);

        // Act

        $result = $this->jwtTokenIssuer->refresh($tokens->authToken);

        // Assert

        $this->assertNull($result);
    }

    public function test_it_returns_user_from_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $tokens = $this->jwtTokenIssuer->issue($user);

        // Act

        $retrievedUser = $this->jwtTokenIssuer->userFromToken($tokens->authToken);

        // Assert

        $this->assertTrue($user->is($retrievedUser));
    }

    public function test_user_from_token_returns_null_on_invalid_token(): void
    {
        // Act

        $retrievedUser = $this->jwtTokenIssuer->userFromToken('invalid.token.here');

        // Assert

        $this->assertNull($retrievedUser);
    }

    /*
     * Asserts.
     */

    public function assertTokensAreValid(IssuedTokens $tokens): void
    {
        $authPayload = JWTAuth::setToken($tokens->authToken)->getPayload();
        $this->assertEquals('access', $authPayload->get('type'));

        $refreshPayload = JWTAuth::setToken($tokens->refreshToken)->getPayload();
        $this->assertEquals('refresh', $refreshPayload->get('type'));
    }
}
