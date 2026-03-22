<?php

namespace Tests\App\Modules\Auth\Models;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(User::class)]
#[Group('authentication')]
#[Group('models')]
class UserRelationshipsTest extends FunctionalTestCase
{
    public function test_it_has_oauth_accounts_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserOAuthAccount::factory()->for($user)->create();

        // Act

        $actual = $user->oauthAccounts;

        // Assert

        $this->assertCount(1, $actual);

        $this->assertInstanceOf(UserOAuthAccount::class, $actual->first());
    }

    public function test_it_has_two_factor_auth_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()->for($user)->create();

        // Act

        $actual = $user->twoFactorAuth;

        // Assert

        $this->assertInstanceOf(UserTwoFactorAuth::class, $actual);

        $this->assertTrue($actual->user->is($user));
    }

    public function test_it_has_magic_links_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();

        MagicLink::factory()->for($user)->count(2)->create();

        // Act

        $actual = $user->magicLinks;

        // Assert

        $this->assertCount(2, $actual);

        $this->assertInstanceOf(MagicLink::class, $actual->first());
    }
}
