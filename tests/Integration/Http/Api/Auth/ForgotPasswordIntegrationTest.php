<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\ForgotPasswordController;
use App\Http\Api\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Actions\ForgotPasswordAction;
use App\Modules\Auth\DataTransferObjects\ForgotPasswordData;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ForgotPasswordController::class)]
#[CoversClass(ForgotPasswordRequest::class)]
class ForgotPasswordIntegrationTest extends FunctionalTestCase
{
    public function test_it_returns_success_regardless_of_user_existence_to_prevent_enumeration(): void
    {
        // Arrange

        $email = fake()->safeEmail();

        User::factory()->create([
            'email' => $email,
        ]);

        // Anticipate

        $forgotPasswordActionMock = $this->mock(
            ForgotPasswordAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn(false),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.passwords.requests.store'),
            [
                'email' => $email,
            ],
        );

        // Assert

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'message' => 'If an account exists, a password reset link has been sent.',
            ],
        ]);

        $forgotPasswordActionMock->shouldHaveReceived('execute')
            ->withArgs(function (ForgotPasswordData $data) use ($email): true {
                $this->assertSame($email, $data->user->email);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->postJson(
            route('api.v1.auth.passwords.requests.store'),
            [
                'email' => 'anything@example.com',
            ],
        );

        // Assert

        $response->assertOk();
    }
}
