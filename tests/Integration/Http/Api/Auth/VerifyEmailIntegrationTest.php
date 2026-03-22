<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\VerifyEmailController;
use App\Modules\Auth\Actions\VerifyEmailAction;
use App\Modules\Shared\Models\User;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[Group('authentication')]
#[CoversClass(VerifyEmailController::class)]
class VerifyEmailIntegrationTest extends FunctionalTestCase
{
    public function test_it_verifies_email(): void
    {
        // Arrange

        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
            true
        );

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'Email verified successfully.']]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->id === $user->id);
    }

    public function test_it_rejects_invalid_signature(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        // Act

        $response = $this->postJson("/api/v1/auth/email/verify/{$user->id}/invalid-hash?expires=9999999999&signature=invalid");

        // Assert

        $response->assertStatus(403);
    }

    public function test_it_returns_already_verified(): void
    {
        // Arrange

        $user = User::factory()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
            true
        );

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertOk()
            ->assertJson(['data' => ['message' => 'Email verified successfully.']]);
    }

    public function test_it_returns_not_found_for_missing_user(): void
    {
        // Arrange

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(15),
            [
                'id' => 999999,
                'hash' => sha1('fake@example.com'),
            ],
            true
        );

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertNotFound()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'No query results for model'));
    }

    public function test_it_returns_forbidden_for_invalid_hash(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(15),
            [
                'id' => $user->id,
                'hash' => sha1('wrongemail@example.com'),
            ],
            true
        );

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertStatus(422)
            ->assertJson(['data' => ['message' => 'Invalid request.']]);
    }

    public function test_it_returns_server_error_when_action_fails(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(15),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
            true
        );

        // Anticipate

        $this->mock(VerifyEmailAction::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->andThrow(new Exception('Unable to verify email.'));
        });

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertStatus(500)
            ->assertJson(['message' => 'Unable to verify email.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            Date::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
            true
        );

        // Act

        $response = $this->postJson($url);

        // Assert

        $response->assertOk();
    }
}
