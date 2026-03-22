<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\EnableTwoFactorController;
use App\Http\Api\Auth\Resources\TwoFactorSetupResource;
use App\Modules\Auth\Actions\EnableTwoFactorAuthAction;
use App\Modules\Auth\DataTransferObjects\TwoFactorSetupPayload;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(EnableTwoFactorController::class)]
#[CoversClass(TwoFactorSetupResource::class)]
class EnableTwoFactorIntegrationTest extends FunctionalTestCase
{
    public function test_it_requires_authentication(): void
    {
        // Act

        $response = $this->postJson(route('api.v1.auth.two-factor.store'));

        // Assert

        $response->assertUnauthorized();
    }

    public function test_it_calls_action_to_enable_2fa(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->actingAs($user);

        $twoFactorSetupPayload = new TwoFactorSetupPayload('secret-data', 'qr-url', ['code1', 'code2']);

        $enableTwoFactorAuthActionMock = $this->mock(EnableTwoFactorAuthAction::class);

        // Anticipate

        $enableTwoFactorAuthActionMock->expects('execute')->with($user)->andReturn($twoFactorSetupPayload);

        // Act

        $response = $this->postJson(route('api.v1.auth.two-factor.store'));

        // Assert

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'secret' => 'secret-data',
                'qr_code_url' => 'qr-url',
                'recovery_codes' => ['code1', 'code2'],
            ],
        ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->actingAs($user);

        // Act

        $response = $this->postJson(route('api.v1.auth.two-factor.store'));

        // Assert

        $response->assertOk();
    }
}
