<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\LogoutController;
use App\Modules\Auth\Actions\LogoutAction;
use App\Modules\Auth\Services\TokenIssuers\JwtTokenIssuer;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LogoutController::class)]
class LogoutIntegrationTest extends FunctionalTestCase
{
    public function test_it_successfully_logs_out_and_invalidates_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->actingAs($user);

        // Anticipate

        $this->mock(LogoutAction::class, fn (MockInterface $mock) => $mock->expects('execute')->once());

        // Act

        $response = $this->postJson(route('api.v1.auth.logout'));

        // Assert

        $response->assertNoContent();
    }

    public function test_it_requires_authentication_to_logout(): void
    {
        $this
            ->postJson(route('api.v1.auth.logout'))
            ->assertUnauthorized();
    }

    public function test_it_integrates_stateless(): void
    {
        // Arrange

        $this->skipTestWhenJwtIsNotAvailable();

        $user = User::factory()->create();

        $this->withToken(
            resolve(JwtTokenIssuer::class)->useGuard('jwt')->issue($user)->authToken,
        );

        $this->actingAs($user);

        // Act

        $response = $this->postJson(route('api.v1.auth.logout'));

        // Assert

        $response->assertNoContent();
    }

    public function test_it_integrates_stateful(): void
    {
        // Arrange

        if (config('auth.guards.api.guard') !== 'web') {
            $this->markTestSkipped('The app is stateless.');
        }

        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        // Act

        $response = $this->postJson(route('api.v1.auth.logout'));

        // Assert

        $response->assertNoContent();
    }
}
