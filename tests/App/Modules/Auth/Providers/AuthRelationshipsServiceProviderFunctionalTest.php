<?php

namespace Tests\App\Modules\Auth\Providers;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Providers\AuthRelationshipsServiceProvider;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(AuthRelationshipsServiceProvider::class)]
#[Group('authentication')]
class AuthRelationshipsServiceProviderFunctionalTest extends FunctionalTestCase
{
    public function test_it_registers_user_relationships(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act & Assert

        $this->assertInstanceOf(HasMany::class, $user->oauthAccounts());
        $this->assertEquals(UserOAuthAccount::class, $user->oauthAccounts()->getRelated()::class);

        $this->assertInstanceOf(HasOne::class, $user->twoFactorAuth());
        $this->assertEquals(UserTwoFactorAuth::class, $user->twoFactorAuth()->getRelated()::class);

        $this->assertInstanceOf(HasMany::class, $user->magicLinks());
        $this->assertEquals(MagicLink::class, $user->magicLinks()->getRelated()::class);
    }
}
