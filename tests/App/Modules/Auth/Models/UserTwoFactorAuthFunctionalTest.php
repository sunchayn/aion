<?php

namespace Tests\App\Modules\Auth\Models;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(UserTwoFactorAuth::class)]
#[Group('authentication')]
#[Group('models')]
class UserTwoFactorAuthFunctionalTest extends FunctionalTestCase
{
    public function test_it_has_correct_casts(): void
    {
        // Arrange

        $twoFactor = UserTwoFactorAuth::factory()->create([
            'secret' => 'test-secret',
            'recovery_codes' => ['code1', 'code2'],
            'confirmed_at' => now(),
        ]);

        // Act

        $actual = $twoFactor->fresh();

        // Assert

        $this->assertEquals('test-secret', $actual->secret);
        $this->assertEquals(['code1', 'code2'], $actual->recovery_codes);
        $this->assertInstanceOf(CarbonImmutable::class, $actual->confirmed_at);
    }

    public function test_it_has_user_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();

        $twoFactor = UserTwoFactorAuth::factory()->for($user)->create();

        // Act

        $actual = $twoFactor->user;

        // Assert

        $this->assertInstanceOf(User::class, $actual);

        $this->assertTrue($actual->is($user));
    }
}
