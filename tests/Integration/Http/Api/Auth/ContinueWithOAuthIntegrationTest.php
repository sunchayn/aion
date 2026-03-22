<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\ContinueWithOAuthController;
use App\Http\Api\Auth\Requests\ContinueWithOAuthRequest;
use App\Modules\Auth\Actions\ContinueWithOAuthAction;
use App\Modules\Auth\DataTransferObjects\ContinueWithOAuthData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\OAuthProviders\Enum\OAuthTokenTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\Mockers\MocksOAuthProviders;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ContinueWithOAuthController::class)]
#[CoversClass(ContinueWithOAuthRequest::class)]
class ContinueWithOAuthIntegrationTest extends FunctionalTestCase
{
    use MocksOAuthProviders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenSocialiteIsNotAvailable();
    }

    public function test_it_calls_action_to_continue_oauth_login(): void
    {
        // Arrange

        $token = fake()->uuid();

        $provider = ProviderEnum::Google;

        $socialiteUser = $this->mockSocialiteUser(
            id: 'google-id-123',
            email: fake()->safeEmail(),
            name: fake()->name(),
        );

        $dummyLoggedInUser = new TokenLoginResponse(
            user: new User(['id' => fake()->randomNumber()]),
            authToken: $token,
            refreshToken: $token,
        );

        // Anticipate

        $oauthProvider = $this->mockOAuthProviderToReturn($socialiteUser);

        $continueWithOAuthActionMock = $this->mock(
            ContinueWithOAuthAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->with(Mockery::type(ContinueWithOAuthData::class))
                ->once()
                ->andReturn($dummyLoggedInUser),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.oauth-tokens.store'),
            [
                'provider' => $provider->value,
                'token' => $token,
            ],
        );

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'user_id' => $dummyLoggedInUser->getUser()->id,
                'two_factor' => false,
                'email' => $dummyLoggedInUser->getUser()->email,
                'name' => $dummyLoggedInUser->getUser()->name,
                'email_verified' => false,
                'avatar_url' => null,
                'oauth_providers' => [],
                'auth_token' => $token,
                'refresh_token' => $token,
            ],
        ]);

        $continueWithOAuthActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (ContinueWithOAuthData $dataArg) use ($provider, $socialiteUser): true {
                $this->assertSame($provider, $dataArg->provider);
                $this->assertSame($socialiteUser, $dataArg->socialiteUser);

                return true;
            });

        $oauthProvider
            ->shouldHaveReceived('fetchUser')
            ->withArgs(function (OAuthToken $oauthTokenArg) use ($token): true {
                $this->assertEquals(
                    $token,
                    $oauthTokenArg->value,
                );

                $this->assertEquals(
                    OAuthTokenTypeEnum::AccessToken,
                    $oauthTokenArg->type,
                );

                return true;
            })
            ->once();
    }

    public function test_it_calls_action_to_continue_oauth_login_with_authorization_code(): void
    {
        // Arrange

        $token = fake()->uuid();

        $provider = ProviderEnum::Google;

        $socialiteUser = $this->mockSocialiteUser(
            id: 'google-id-123',
            email: fake()->safeEmail(),
            name: fake()->name(),
        );

        $dummyLoggedInUser = new TokenLoginResponse(
            user: new User(['id' => fake()->randomNumber()]),
            authToken: fake()->uuid(),
            refreshToken: fake()->uuid(),
        );

        // Anticipate

        $oauthProvider = $this->mockOAuthProviderToReturn($socialiteUser);

        $this->mock(
            ContinueWithOAuthAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->with(Mockery::type(ContinueWithOAuthData::class))
                ->once()
                ->andReturn($dummyLoggedInUser),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.oauth-tokens.store'),
            [
                'provider' => $provider->value,
                'token' => $token,
                'type' => 'authorization_token',
            ],
        );

        // Assert

        $response->assertOk();

        $oauthProvider
            ->shouldHaveReceived('fetchUser')
            ->withArgs(function (OAuthToken $oauthTokenArg) use ($token): true {
                $this->assertEquals(
                    $token,
                    $oauthTokenArg->value,
                );

                $this->assertEquals(
                    OAuthTokenTypeEnum::AuthorizationToken,
                    $oauthTokenArg->type,
                );

                return true;
            })
            ->once();
    }

    public function test_it_returns_error_when_unable_to_continue_with_method(): void
    {
        // Anticipate

        $this
            ->mock(
                ContinueWithOAuthAction::class,
                fn (MockInterface $mock) => $mock->expects('execute')->andThrow(new UnableToContinueWithOAuthException),
            );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.oauth-tokens.store'),
            [
                'provider' => ProviderEnum::Google,
                'token' => fake()->uuid(),
            ],
        );

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(
            [
                'token' => 'Unable to authenticate using this method. Please try again or use a different method.',
            ]
        );
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->postJson(
            route('api.v1.auth.oauth-tokens.store'),
            [
                'provider' => ProviderEnum::Google->value,
                'token' => '::token::',
            ],
        );

        // Assert

        $this->assertFalse($response->isServerError());
    }
}
