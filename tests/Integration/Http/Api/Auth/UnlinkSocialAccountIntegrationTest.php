<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\UnlinkSocialAccountController;
use App\Modules\Auth\Actions\UnlinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\UnlinkSocialAccountData;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(UnlinkSocialAccountController::class)]
class UnlinkSocialAccountIntegrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenSocialiteIsNotAvailable();
    }

    public function test_it_successfully_unlinks_social_account(): void
    {
        // Arrange

        $user = User::factory()->create();
        $provider = ProviderEnum::Google;

        // Anticipate

        $this->mock(UnlinkSocialAccountAction::class, function (MockInterface $mock) use ($user, $provider): void {
            $mock->expects('execute')->once()->with(Mockery::on(fn (UnlinkSocialAccountData $data): bool => $data->user->is($user) && $data->provider === $provider));
        });

        // Act

        $response = $this->actingAs($user)->deleteJson(
            route('api.v1.auth.oauth-links.destroy', ['provider' => $provider->value])
        );

        // Assert

        $response->assertOk();
        $response->assertJson(['data' => ['message' => 'Account unlinked successfully.']]);
    }

    public function test_it_requires_authentication_to_unlink(): void
    {
        // Act

        $response = $this->deleteJson(
            route('api.v1.auth.oauth-links.destroy', ['provider' => 'google'])
        );

        // Assert

        $response->assertUnauthorized();
    }

    public function test_it_fails_with_invalid_provider(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $response = $this->actingAs($user)->deleteJson(
            route('api.v1.auth.oauth-links.destroy', ['provider' => 'invalid-provider'])
        );

        // Assert

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['provider']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();
        $provider = ProviderEnum::Google;

        UserOAuthAccount::factory()
            ->for($user)
            ->create(['provider' => $provider]);

        // Act

        $response = $this->actingAs($user)->deleteJson(
            route('api.v1.auth.oauth-links.destroy', ['provider' => $provider->value])
        );

        // Assert

        $response->assertOk();
    }
}
