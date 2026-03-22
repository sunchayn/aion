<?php

declare(strict_types=1);

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\UnlinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\UnlinkSocialAccountData;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(UnlinkSocialAccountAction::class)]
#[CoversClass(UnlinkSocialAccountData::class)]
#[Group('authentication')]
#[Group('oauth')]
class UnlinkSocialAccountActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_unlinks_social_account(): void
    {
        // Arrange

        $user = User::factory()->create();

        $provider = Arr::random(ProviderEnum::cases());

        UserOAuthAccount::factory()
            ->for($user)
            ->create(['provider' => $provider]);

        $data = new UnlinkSocialAccountData($user, $provider);

        $unlinkSocialAccountAction = resolve(UnlinkSocialAccountAction::class);

        // Act

        $unlinkSocialAccountAction->execute($data);

        // Assert

        $this->assertDatabaseMissing('user_oauth_accounts', [
            'user_id' => $user->id,
            'provider' => $provider->value,
        ]);

        $user->refresh();

        $this->assertCount(0, $user->oauthAccounts);

        $this->assertNull($user->oauthAccounts()->where('provider', $provider)->first());
    }

    public function test_it_does_not_unlink_other_providers(): void
    {
        // Arrange

        $user = User::factory()->create();

        $targetProvider = Arr::random(ProviderEnum::cases());

        $otherProvider = Arr::random(
            Arr::exceptValues(ProviderEnum::cases(), [$targetProvider]),
        );

        UserOAuthAccount::factory()
            ->for($user)
            ->create(['provider' => $targetProvider]);

        UserOAuthAccount::factory()
            ->for($user)
            ->create(['provider' => $otherProvider]);

        $data = new UnlinkSocialAccountData($user, $targetProvider);

        $unlinkSocialAccountAction = resolve(UnlinkSocialAccountAction::class);

        // Act

        $unlinkSocialAccountAction->execute($data);

        // Assert

        $this->assertDatabaseMissing(
            'user_oauth_accounts', [
                'user_id' => $user->id,
                'provider' => $targetProvider->value,
            ]);

        $this->assertDatabaseHas(
            'user_oauth_accounts',
            [
                'user_id' => $user->id,
                'provider' => $otherProvider->value,
            ]);

        $this->assertCount(1, $user->refresh()->oauthAccounts);
    }

    public function test_it_does_not_unlink_other_users_accounts(): void
    {
        // Arrange

        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        UserOAuthAccount::factory()
            ->for($otherUser)
            ->create(['provider' => ProviderEnum::Google]);

        $data = new UnlinkSocialAccountData($user, ProviderEnum::Google);

        $unlinkSocialAccountAction = resolve(UnlinkSocialAccountAction::class);

        // Act

        $unlinkSocialAccountAction->execute($data);

        // Assert

        $this->assertDatabaseHas(
            'user_oauth_accounts',
            [
                'user_id' => $otherUser->id,
                'provider' => ProviderEnum::Google->value,
            ],
        );
    }
}
