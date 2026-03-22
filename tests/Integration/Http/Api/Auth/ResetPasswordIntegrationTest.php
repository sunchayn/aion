<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\ResetPasswordController;
use App\Http\Api\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Actions\ResetPasswordAction;
use App\Modules\Auth\DataTransferObjects\ResetPasswordData;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ResetPasswordController::class)]
#[CoversClass(ResetPasswordRequest::class)]
class ResetPasswordIntegrationTest extends FunctionalTestCase
{
    public function test_it_returns_success_on_successful_reset(): void
    {
        // Arrange

        $email = fake()->safeEmail();
        $password = fake()->password(minLength: 8);
        $token = fake()->uuid();

        // Anticipate

        $resetPasswordActionMock = $this->mock(
            ResetPasswordAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn(true)
        );

        // Act

        $response = $this->patchJson(
            route('api.v1.auth.passwords.resets.patch'),
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
        );

        // Assert

        $response->assertOk();
        $response->assertExactJson(['data' => ['message' => 'Your password has been reset.']]);

        $resetPasswordActionMock->shouldHaveReceived('execute')
            ->withArgs(function (ResetPasswordData $data) use ($email, $password, $token): true {
                $this->assertSame($email, $data->email);
                $this->assertSame($password, $data->password);
                $this->assertSame($token, $data->token);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->patchJson(
            route('api.v1.auth.passwords.resets.patch'),
            [
                'token' => 'invalid-token',
                'email' => 'test@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ],
        );

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'email' => 'The password reset token is invalid',
        ]);
    }
}
