<?php

namespace Tests\App\Modules\Auth\OAuthProviders\ValueObjects;

use App\Modules\Auth\OAuthProviders\Enum\OAuthTokenTypeEnum;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(OAuthToken::class)]
#[Group('authentication')]
#[Group('oauth')]
class OAuthTokenUnitTest extends UnitTestCase
{
    public function test_it_creates_authorization_code_token(): void
    {
        // Arrange

        $value = fake()->uuid();

        // Act

        $token = OAuthToken::authorizationToken($value);

        // Assert

        $this->assertEquals($value, $token->value);
        $this->assertEquals(OAuthTokenTypeEnum::AuthorizationToken, $token->type);
        $this->assertTrue($token->isAuthorizationCode());
        $this->assertFalse($token->isAccessToken());
    }

    public function test_it_creates_access_token(): void
    {
        // Arrange

        $value = fake()->uuid();

        // Act

        $token = OAuthToken::accessToken($value);

        // Assert

        $this->assertEquals($value, $token->value);
        $this->assertEquals(OAuthTokenTypeEnum::AccessToken, $token->type);
        $this->assertTrue($token->isAccessToken());
        $this->assertFalse($token->isAuthorizationCode());
    }

    #[DataProvider('tokenTypeDataProvider')]
    public function test_type_checks(OAuthToken $token, bool $isAccess, bool $isAuth): void
    {
        // Assert

        $this->assertEquals($isAccess, $token->isAccessToken());

        $this->assertEquals($isAuth, $token->isAuthorizationCode());
    }

    public static function tokenTypeDataProvider(): Generator
    {
        yield 'access token' => [
            'token' => OAuthToken::accessToken('foo'),
            'isAccess' => true,
            'isAuth' => false,
        ];

        yield 'authorization code' => [
            'token' => OAuthToken::authorizationToken('bar'),
            'isAccess' => false,
            'isAuth' => true,
        ];
    }
}
