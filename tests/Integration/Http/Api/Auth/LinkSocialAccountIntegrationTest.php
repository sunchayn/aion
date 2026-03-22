<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\LinkSocialAccountController;
use App\Http\Api\Auth\Requests\LinkSocialAccountRequest;
use App\Modules\Auth\Actions\LinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\LinkSocialAccountData;
use App\Modules\Auth\OAuthProviders\Enum\OAuthTokenTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Arr;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\Mockers\MocksOAuthProviders;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LinkSocialAccountController::class)]
#[CoversClass(LinkSocialAccountRequest::class)]
class LinkSocialAccountIntegrationTest extends FunctionalTestCase
{
    use MocksOAuthProviders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenSocialiteIsNotAvailable();
    }

    public function test_it_calls_action_to_link_social_account(): void
    {
        // Arrange

        $token = fake()->uuid();

        $provider = Arr::random(ProviderEnum::cases());

        $user = User::factory()->create();

        // Anticipate

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'mocked-test-id',
            avatar: 'https://avatar.url',
            token: 'access',
            refreshToken: 'refresh',
            expiresIn: 3600,
        );

        $this->mockOAuthProviderToReturn($oauthUserMock, $provider);

        $linkSocialAccountActionMock = $this->mock(
            LinkSocialAccountAction::class,
            fn (MockInterface $mock) => $mock
                ->expects('execute')
                ->with(Mockery::type(LinkSocialAccountData::class))
                ->once(),
        );

        // Act

        $response = $this->actingAs($user)->postJson(
            route('api.v1.auth.oauth-links.store'),
            [
                'provider' => $provider->value,
                'token' => $token,
            ],
        );

        // Assert

        $response->assertOk();
        $response->assertJson(['data' => ['message' => 'Account linked successfully.']]);

        $linkSocialAccountActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (LinkSocialAccountData $dataArg) use ($provider, $user, $token): true {
                $this->assertSame($provider, $dataArg->provider);
                $this->assertTrue($user->is($dataArg->authenticatedUser));
                $this->assertEquals($token, $dataArg->token->value);

                return true;
            });
    }

    public function test_it_calls_action_to_link_social_account_with_authorization_code_token(): void
    {
        // Arrange

        $token = fake()->uuid();

        $provider = Arr::random(ProviderEnum::cases());

        $user = User::factory()->create();

        // Anticipate

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'mocked-test-id',
            avatar: 'https://avatar.url',
            token: 'access',
            refreshToken: 'refresh',
            expiresIn: 3600,
        );

        $this->mockOAuthProviderToReturn($oauthUserMock, $provider);

        $linkSocialAccountActionMock = $this->mock(
            LinkSocialAccountAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->with(Mockery::type(LinkSocialAccountData::class))
                ->once(),
        );

        // Act

        $response = $this->actingAs($user)->postJson(
            route('api.v1.auth.oauth-links.store'),
            [
                'provider' => $provider->value,
                'token' => $token,
                'type' => OAuthTokenTypeEnum::AuthorizationToken->value,
            ],
        );

        // Assert

        $response->assertOk();
        $response->assertJson(['data' => ['message' => 'Account linked successfully.']]);

        $linkSocialAccountActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (LinkSocialAccountData $dataArg) use ($provider, $user, $token): true {
                $this->assertSame($provider, $dataArg->provider);
                $this->assertTrue($user->is($dataArg->authenticatedUser));
                $this->assertEquals($token, $dataArg->token->value);
                $this->assertTrue($dataArg->token->isAuthorizationCode());

                return true;
            });
    }

    public function test_it_returns_error_when_unable_to_link(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Anticipate

        $this->mockOAuthProviderToReturn(null);

        // Act

        $response = $this->actingAs($user)->postJson(
            route('api.v1.auth.oauth-links.store'),
            [
                'provider' => ProviderEnum::Google->value,
                'token' => fake()->uuid(),
            ],
        );

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(
            [
                'token' => 'Unable to link account using this method. Please try again or use a different method.',
            ]
        );
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->mockOAuthProviderToReturn($this->mockSocialiteUser(id: 'oauth-id'));

        // Act

        $response = $this->actingAs($user)->postJson(
            route('api.v1.auth.oauth-links.store'),
            [
                'provider' => ProviderEnum::Google->value,
                'token' => fake()->uuid(),
            ],
        );

        // Assert

        $response->assertOk();
    }
}
