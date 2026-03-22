<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\RegenerateTwoFactorRecoveryCodesController;
use App\Modules\Auth\Actions\RegenerateTwoFactorRecoveryCodesAction;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegenerateTwoFactorRecoveryCodesController::class)]
#[Group('authentication')]
#[Group('2fa')]
class RegenerateTwoFactorRecoveryCodesIntegrationTest extends FunctionalTestCase
{
    public function test_it_regenerates_recovery_codes_successfully(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $newCodes = ['new-code-1', 'new-code-2'];

        $this->actingAs($user);

        // Anticipate

        $this->mock(
            RegenerateTwoFactorRecoveryCodesAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withArgs(fn (User $u) => $u->is($user))
                ->andReturn($newCodes)
        );

        // Act

        $response = $this->postJson(route('api.v1.auth.two-factor.recovery-codes.store'));

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'recovery_codes' => $newCodes,
            ],
            'message' => 'Recovery codes have been successfully regenerated.',
        ]);
    }

    public function test_it_returns_bad_request_if_2fa_is_not_enabled(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->actingAs($user);

        // Anticipate

        $this->mock(
            RegenerateTwoFactorRecoveryCodesAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturnNull()
        );

        // Act

        $response = $this->postJson(route('api.v1.auth.two-factor.recovery-codes.store'));

        // Assert

        $response->assertBadRequest();

        $response->assertJson([
            'message' => 'Two-factor authentication must be enabled to regenerate recovery codes.',
        ]);
    }

    public function test_it_requires_authentication(): void
    {
        $this
            ->postJson(route('api.v1.auth.two-factor.recovery-codes.store'))
            ->assertUnauthorized();
    }

    public function test_it_integrates(): void
    {
        // Arrange
        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.auth.two-factor.recovery-codes.store'));

        // Assert
        $response->assertOk();
    }
}
