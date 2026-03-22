<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\TwoFactorChallengeController;
use App\Http\Api\Auth\Requests\TwoFactorChallengeRequest;
use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\TwoFactorChallengeAction;
use App\Modules\Auth\DataTransferObjects\TwoFactorChallengeData;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Crypt;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(TwoFactorChallengeController::class)]
#[CoversClass(TwoFactorChallengeRequest::class)]
#[Group('authentication')]
#[Group('2fa')]
class TwoFactorChallengeIntegrationTest extends FunctionalTestCase
{
    public function test_it_verifies_2fa_code(): void
    {
        // Arrange

        $user = User::factory()->create();

        $userTwoAuthFactor = UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $code = fake()->numerify('??????');

        // Anticipate

        $dummyLoggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: 'new-auth-token',
            refreshToken: 'new-refresh-token',
        );

        $this->mock(
            LoginAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturn($dummyLoggedInUser),
        );

        $twoFactorChallengeActionMock = $this->mock(
            TwoFactorChallengeAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturn(true),
        );

        $tokenPayload = json_encode(['id' => $user->id, 'expires_at' => now()->addMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'code' => $code,
                    'two_factor_token' => $twoFactorToken,
                ],
            )
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'auth_token',
                    'refresh_token',
                    'two_factor',
                ],
            ]);

        $twoFactorChallengeActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (TwoFactorChallengeData $data) use ($code, $userTwoAuthFactor): true {
                $this->assertTrue($userTwoAuthFactor->is($data->twoFactorAuth));
                $this->assertEquals($code, $data->code);

                return true;
            });
    }

    public function test_it_verifies_valid_recovery_code(): void
    {
        // Arrange

        $user = User::factory()->create();

        $userTwoAuthFactor = UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $recoveryCode = 'valid-recovery-code';

        // Anticipate

        $dummyLoggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: 'new-auth-token',
            refreshToken: 'new-refresh-token',
        );

        $this->mock(
            LoginAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturn($dummyLoggedInUser),
        );

        $twoFactorChallengeActionMock = $this->mock(
            TwoFactorChallengeAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturn(true),
        );

        $tokenPayload = json_encode(['id' => $user->id, 'expires_at' => now()->addMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'recovery_code' => $recoveryCode,
                    'two_factor_token' => $twoFactorToken,
                ],
            )
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'auth_token',
                    'refresh_token',
                    'two_factor',
                ],
            ]);

        $twoFactorChallengeActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (TwoFactorChallengeData $data) use ($recoveryCode, $userTwoAuthFactor): true {
                $this->assertTrue($userTwoAuthFactor->is($data->twoFactorAuth));
                $this->assertNull($data->code);
                $this->assertEquals($recoveryCode, $data->recoveryCode);

                return true;
            });
    }

    public function test_it_integrates_invalid_recovery_code(): void
    {
        // Arrange

        $user = User::factory()
            ->has(
                UserTwoFactorAuth::factory()->confirmed(),
                'twoFactorAuth'
            )
            ->create();

        $tokenPayload = json_encode(['id' => $user->id, 'expires_at' => now()->addMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'recovery_code' => 'invalid',
                    'two_factor_token' => $twoFactorToken,
                ],
            )
            ->assertUnprocessable()
            ->assertJson(['message' => 'The provided code was invalid.']);
    }

    public function test_it_returns_unauthorized_for_expired_token(): void
    {
        // Arrange

        $tokenPayload = json_encode(['id' => 1, 'expires_at' => now()->subMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'two_factor_token' => $twoFactorToken,
                    'code' => '123456',
                ],
            )
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_it_returns_unauthorized_for_malformed_token(): void
    {
        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'two_factor_token' => 'invalid-encrypted-string',
                    'code' => '123456',
                ],
            )
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_it_returns_not_found_for_missing_user(): void
    {
        // Arrange

        $tokenPayload = json_encode(['id' => 999999, 'expires_at' => now()->addMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'two_factor_token' => $twoFactorToken,
                    'code' => '123456',
                ],
            )
            ->assertNotFound()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'No query results for model'));
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()
            ->has(
                UserTwoFactorAuth::factory()->confirmed(),
                'twoFactorAuth'
            )
            ->create();

        $this->mock(TwoFactorAuthManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->andReturnFalse();
        });

        $tokenPayload = json_encode(['id' => $user->id, 'expires_at' => now()->addMinutes(15)->timestamp]);

        $twoFactorToken = Crypt::encryptString($tokenPayload);

        // Act & Assert

        $this
            ->postJson(
                route('api.v1.auth.two-factor.challenges.store'),
                [
                    'code' => 'invalid',
                    'two_factor_token' => $twoFactorToken,
                ],
            )
            ->assertUnprocessable()
            ->assertJson(['message' => 'The provided code was invalid.']);
    }
}
