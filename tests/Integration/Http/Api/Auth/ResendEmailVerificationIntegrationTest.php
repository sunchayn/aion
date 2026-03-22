<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\ResendEmailVerificationController;
use App\Modules\Auth\Actions\ResendEmailVerificationAction;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[Group('authentication')]
#[CoversClass(ResendEmailVerificationController::class)]
class ResendEmailVerificationIntegrationTest extends FunctionalTestCase
{
    public function test_it_resends_verification_email(): void
    {
        // Arrange

        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        // Act

        $response = $this->postJson(route('api.v1.auth.verification.send'));

        // Assert

        $response
            ->assertOk()
            ->assertJson(['data' => ['message' => 'Verification link sent.']]);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_it_returns_already_verified_if_already_verified(): void
    {
        // Arrange

        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        // Act

        $response = $this->postJson(route('api.v1.auth.verification.send'));

        // Assert

        $response
            ->assertOk()
            ->assertJson(['data' => ['message' => 'Email is already verified.']]);

        Notification::assertNothingSent();
    }

    public function test_it_requires_authentication(): void
    {
        // Act

        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        // Assert

        $response->assertUnauthorized();
    }

    public function test_it_returns_server_error_if_unable_to_send(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        // Anticipate

        $this->mock(ResendEmailVerificationAction::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->andReturnFalse();
        });

        // Act

        $response = $this->postJson(route('api.v1.auth.verification.send'));

        // Assert

        $response
            ->assertServerError()
            ->assertJson(['data' => ['message' => 'Unable to send verification link.']]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        Notification::fake();

        $user = User::factory()->unverified()->create();

        // Act

        $response = $this->actingAs($user)->postJson(route('api.v1.auth.verification.send'));

        // Assert

        $response->assertOk();
    }
}
