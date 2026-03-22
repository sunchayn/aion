<?php

namespace Tests\App\Modules\Auth\Models;

use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(UserOAuthAccount::class)]
#[Group('models')]
class UserOAuthAccountFunctionalTest extends FunctionalTestCase
{
    public function test_it_has_correct_casts(): void
    {
        // Arrange

        $oauthAccount = UserOAuthAccount::factory()->create([
            'provider' => ProviderEnum::Google,
            'expires_at' => now()->addHour(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ]);

        // Act

        $actual = $oauthAccount->fresh();

        // Assert

        $this->assertSame(ProviderEnum::Google, $actual->provider);
        $this->assertInstanceOf(CarbonImmutable::class, $actual->expires_at);
        $this->assertEquals('test-access-token', $actual->access_token);
        $this->assertEquals('test-refresh-token', $actual->refresh_token);
    }

    public function test_it_has_user_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();

        $oauthAccount = UserOAuthAccount::factory()->for($user)->create();

        // Act

        $actual = $oauthAccount->user;

        // Assert

        $this->assertInstanceOf(User::class, $actual);

        $this->assertTrue($actual->is($user));
    }
}
