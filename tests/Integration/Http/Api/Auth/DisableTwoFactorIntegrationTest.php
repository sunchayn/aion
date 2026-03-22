<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\DisableTwoFactorController;
use App\Modules\Auth\Actions\DisableTwoFactorAuthAction;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(DisableTwoFactorController::class)]
#[Group('authentication')]
#[Group('2fa')]
class DisableTwoFactorIntegrationTest extends FunctionalTestCase
{
    public function test_it_disables_two_factor_auth(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $this->actingAs($user);

        $disableTwoFactorActionMock = $this->spy(DisableTwoFactorAuthAction::class);

        // Act

        $response = $this->deleteJson(route('api.v1.auth.two-factor.destroy'));

        // Assert

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'message' => 'Two-factor authentication disabled successfully.',
            ],
        ]);

        $disableTwoFactorActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (User $actionUser) use ($user): true {
                $this->assertTrue($user->is($actionUser));

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $this->actingAs($user);

        // Act

        $response = $this->deleteJson(route('api.v1.auth.two-factor.destroy'));

        // Assert

        $response->assertOk();
    }
}
