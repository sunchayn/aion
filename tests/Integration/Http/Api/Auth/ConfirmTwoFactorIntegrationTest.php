<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\ConfirmTwoFactorController;
use App\Http\Api\Auth\Requests\ConfirmTwoFactorRequest;
use App\Modules\Auth\Actions\VerifyTwoFactorAuthCodeAction;
use App\Modules\Auth\DataTransferObjects\VerifyTwoFactorAuthCodeData;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Crypt;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ConfirmTwoFactorController::class)]
#[CoversClass(ConfirmTwoFactorRequest::class)]
class ConfirmTwoFactorIntegrationTest extends FunctionalTestCase
{
    public function test_it_confirms_2fa_registration(): void
    {
        // Arrange

        $user = User::factory()
            ->has(
                UserTwoFactorAuth::factory()
                    ->state([
                        'secret' => Crypt::encrypt('secret-key'),
                    ]),
                'twoFactorAuth',
            )
            ->create();

        $this->actingAs($user);

        $code = fake()->numerify('??????');

        // Anticipate

        $verifyTwoFactorAuthCodeActionMock = $this->mock(VerifyTwoFactorAuthCodeAction::class);
        $verifyTwoFactorAuthCodeActionMock->expects('execute')
            ->withAnyArgs()
            ->once()
            ->andReturnTrue();

        // Act

        $response = $this->postJson(
            route('api.v1.auth.two-factor.confirmations.store'),
            [
                'code' => $code,
            ],
        );

        // Assert

        $response->assertOk();
        $response->assertJson(['data' => ['message' => '2FA has been confirmed.']]);

        $verifyTwoFactorAuthCodeActionMock->shouldHaveReceived('execute')
            ->withArgs(function (VerifyTwoFactorAuthCodeData $data) use ($user, $code): true {
                $this->assertTrue($data->twoFactorAuth->is($user->twoFactorAuth));

                $this->assertSame($code, $data->code);

                return true;
            });
    }

    public function test_it_handles_failed_challenges(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->actingAs($user);

        // Anticipate

        $verifyTwoFactorAuthCodeActionMock = $this->mock(VerifyTwoFactorAuthCodeAction::class);

        $verifyTwoFactorAuthCodeActionMock->expects('execute')
            ->withAnyArgs()
            ->once()
            ->andReturnFalse();

        // Act

        $response = $this->postJson(
            route('api.v1.auth.two-factor.confirmations.store'),
            [
                'code' => fake()->numerify('??????'),
            ],
        );

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(['code' => 'The provided code was invalid.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()
            ->has(
                UserTwoFactorAuth::factory()
                    ->state([
                        'secret' => Crypt::encrypt('secret-key'),
                    ]),
                'twoFactorAuth',
            )
            ->create();

        $this->mock(TwoFactorAuthManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->andReturnTrue();
        });

        $this->actingAs($user);

        // Act

        $response = $this->postJson(
            route('api.v1.auth.two-factor.confirmations.store'),
            [
                'code' => fake()->numerify('??????'),
            ],
        );

        // Assert

        $response->assertSuccessful();
    }
}
