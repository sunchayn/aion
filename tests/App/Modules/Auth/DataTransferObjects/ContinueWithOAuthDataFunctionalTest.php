<?php

namespace Tests\App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\DataTransferObjects\ContinueWithOAuthData;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Mockers\MocksOAuthProviders;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ContinueWithOAuthData::class)]
#[Group('authentication')]
#[Group('oauth')]
class ContinueWithOAuthDataFunctionalTest extends FunctionalTestCase
{
    use MocksOAuthProviders;

    public function test_it_creates_object_for_brand_new_user(): void
    {
        // Arrange

        $provider = Arr::random(ProviderEnum::cases());

        $token = fake()->uuid();

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-123',
            email: $oauthUserEmail = fake()->safeEmail(),
            name: $oauthUserName = fake()->name(),
            avatar: 'https://example.com/avatar.jpg',
        );

        $this->mockOAuthProviderToReturn($oauthUserMock);

        // Act

        $actual = ContinueWithOAuthData::fromToken($provider, OAuthToken::authorizationToken($token));

        // Assert

        $this->assertEquals($provider, $actual->provider);
        $this->assertNotNull($actual->socialiteUser);
        $this->assertNull($actual->user);
        $this->assertFalse($actual->alreadyLinked);
        $this->assertSame($oauthUserMock, $actual->socialiteUser);
    }

    public function test_it_creates_object_for_existent_user(): void
    {
        // Arrange

        $provider = Arr::random(ProviderEnum::cases());

        $token = fake()->uuid();

        $user = User::factory()->create();

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-123',
            email: $user->email,
            name: $oauthUserName = fake()->name(),
            avatar: 'https://example.com/avatar.jpg',
        );

        $this->mockOAuthProviderToReturn($oauthUserMock);

        // Act

        $actual = ContinueWithOAuthData::fromToken($provider, OAuthToken::authorizationToken($token));

        // Assert

        $this->assertEquals($provider, $actual->provider);
        $this->assertNotNull($actual->socialiteUser);
        $this->assertTrue($user->is($actual->user));
        $this->assertFalse($actual->alreadyLinked);
        $this->assertSame($oauthUserMock, $actual->socialiteUser);
    }

    public function test_it_creates_object_for_existent_user_that_is_already_linked(): void
    {
        // Arrange

        $provider = Arr::random(ProviderEnum::cases());

        $token = fake()->uuid();

        $user = User::factory()->create();

        $oauthUserMock = $this->mockSocialiteUser(
            id: $oauthId = 'oauth-id-123',
            email: $user->email,
            name: $oauthUserName = fake()->name(),
            avatar: 'https://example.com/avatar.jpg',
        );

        UserOAuthAccount::factory()
            ->for($user)
            ->create([
                'provider' => $provider->value,
                'provider_user_id' => $oauthId,
            ]);

        $this->mockOAuthProviderToReturn($oauthUserMock);

        // Act

        $actual = ContinueWithOAuthData::fromToken($provider, OAuthToken::authorizationToken($token));

        // Assert

        $this->assertEquals($provider, $actual->provider);
        $this->assertNotNull($actual->socialiteUser);
        $this->assertTrue($user->is($actual->user));
        $this->assertTrue($actual->alreadyLinked);
        $this->assertSame($oauthUserMock, $actual->socialiteUser);
    }

    public function test_it_creates_empty_object_when_no_oauth_user_is_found(): void
    {
        // Arrange

        $provider = Arr::random(ProviderEnum::cases());

        $token = fake()->uuid();

        $this->mockOAuthProviderToReturn(null);

        // Act

        $actual = ContinueWithOAuthData::fromToken($provider, OAuthToken::authorizationToken($token));

        // Assert

        $this->assertEquals($provider, $actual->provider);
        $this->assertNull($actual->socialiteUser);
        $this->assertNull($actual->user);
        $this->assertFalse($actual->alreadyLinked);
    }

    public function test_it_creates_empty_object(): void
    {
        // Arrange

        $provider = Arr::random(ProviderEnum::cases());

        // Act

        $actual = ContinueWithOAuthData::empty($provider);

        // Assert

        $this->assertEquals($provider, $actual->provider);
        $this->assertNull($actual->socialiteUser);
        $this->assertNull($actual->user);
        $this->assertFalse($actual->alreadyLinked);
    }
}
