<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\LoginWithCredentialsController;
use App\Http\Api\Auth\Requests\LoginWithCredentialsRequest;
use App\Modules\Auth\Actions\LoginWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\LoginWithCredentialsData;
use App\Modules\Auth\Exceptions\UnableToLoginException;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LoginWithCredentialsController::class)]
#[CoversClass(LoginWithCredentialsRequest::class)]
class LoginIntegrationTest extends FunctionalTestCase
{
    public function test_it_logs_in_user_with_valid_credentials(): void
    {
        // Arrange

        $email = fake()->safeEmail();
        $password = fake()->password();

        $user = User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);

        $dummyLoggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: fake()->uuid(),
            refreshToken: fake()->uuid(),
        );

        // Anticipate

        $loginWithCredentialsActionMock = $this->mock(
            LoginWithCredentialsAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn($dummyLoggedInUser),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.tokens.store'),
            [
                'email' => $email,
                'password' => $password,
            ]);

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'user_id' => $dummyLoggedInUser->getUser()->id,
                'auth_token' => $dummyLoggedInUser->authToken,
                'refresh_token' => $dummyLoggedInUser->refreshToken,
                'two_factor' => false,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => true,
                'avatar_url' => null,
                'oauth_providers' => [],
            ],
        ]);

        $loginWithCredentialsActionMock->shouldHaveReceived('execute')
            ->withArgs(function (LoginWithCredentialsData $data) use ($email, $password): true {
                $this->assertSame($email, $data->user?->email);
                $this->assertSame($password, $data->password);

                return true;
            });
    }

    public function test_it_returns_two_factor_token_when_2fa_enabled(): void
    {
        // Arrange

        $email = fake()->safeEmail();
        $password = fake()->password();

        $user = User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);

        $dummyLoggedInUser = TokenLoginResponse::pendingTwoFactorAuth(
            user: $user,
            twoFactorToken: fake()->uuid(),
        );

        // Anticipate

        $loginWithCredentialsActionMock = $this->mock(
            LoginWithCredentialsAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn($dummyLoggedInUser),
        );

        // Act

        $response = $this->postJson(route('api.v1.auth.tokens.store'), [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'user_id' => $dummyLoggedInUser->getUser()->id,
                'two_factor_token' => $dummyLoggedInUser->twoFactorToken,
                'two_factor' => true,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => true,
                'avatar_url' => null,
                'oauth_providers' => [],
            ],
        ]);

        $loginWithCredentialsActionMock->shouldHaveReceived('execute')
            ->withArgs(function (LoginWithCredentialsData $data) use ($email, $password): true {
                $this->assertSame($email, $data->user?->email);
                $this->assertSame($password, $data->password);

                return true;
            });
    }

    public function test_it_handles_login_errors(): void
    {
        // Anticipate

        $this->mock(
            LoginWithCredentialsAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andThrow(new UnableToLoginException),
        );

        // Act

        $response = $this->postJson(route('api.v1.auth.tokens.store'), [
            'email' => fake()->safeEmail(),
            'password' => fake()->password(),
        ]);

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(
            [
                'email' => 'Unable to log in with the provided credentials.',
            ],
        );
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create([
            'password' => $password = fake()->password(),
        ]);

        // Act

        $response = $this->postJson(route('api.v1.auth.tokens.store'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        // Assert

        $response->assertOk();
    }
}
