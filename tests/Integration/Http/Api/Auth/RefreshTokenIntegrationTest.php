<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\RefreshTokenController;
use App\Http\Api\Auth\Requests\RefreshTokenRequest;
use App\Modules\Auth\Actions\RefreshTokenAction;
use App\Modules\Auth\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RefreshTokenController::class)]
#[CoversClass(RefreshTokenRequest::class)]
#[Group('authentication')]
class RefreshTokenIntegrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenJwtIsNotAvailable();
    }

    public function test_it_returns_new_tokens_on_valid_refresh_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $loggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: 'new-auth-token',
            refreshToken: 'new-auth-token',
        );

        // Anticipate

        $refreshTokenActionMock = $this->mock(
            RefreshTokenAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->with('old-refresh-token')
                ->andReturn($loggedInUser),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.tokens.refresh'),
            ['refresh_token' => 'old-refresh-token'],
        );

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'user_id' => $user->id,
                'auth_token' => 'new-auth-token',
                'refresh_token' => 'new-auth-token',
                'two_factor' => false,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => $user->hasVerifiedEmail(),
                'avatar_url' => $user->avatar_url,
                'oauth_providers' => [],
            ],
        ]);
    }

    public function test_it_returns_401_on_invalid_refresh_token(): void
    {
        // Arrange

        $this->mock(
            RefreshTokenAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andThrow(InvalidRefreshTokenException::becauseTokenIsInvalid()),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.tokens.refresh'),
            ['refresh_token' => 'expired-or-invalid-token'],
        );

        // Assert

        $response->assertUnauthorized();

        $response->assertJson([
            'message' => 'The provided refresh token is invalid or has expired.',
        ]);
    }

    public function test_it_validates_refresh_token_is_required(): void
    {
        $this
            ->postJson(route('api.v1.auth.tokens.refresh'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $response = $this->postJson(
            route('api.v1.auth.tokens.refresh'),
            ['refresh_token' => fake()->uuid()],
        );

        // Assert

        $response->assertUnauthorized();
    }
}
