<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\RegenerateTwoFactorRecoveryCodesAction;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegenerateTwoFactorRecoveryCodesAction::class)]
#[Group('authentication')]
#[Group('2fa')]
class RegenerateTwoFactorRecoveryCodesActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_returns_null_if_user_has_no_two_factor_auth(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $actual = resolve(RegenerateTwoFactorRecoveryCodesAction::class)->execute($user);

        // Assert

        $this->assertNull($actual);
    }

    public function test_it_returns_null_if_two_factor_auth_is_not_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->unconfirmed()
            ->create();

        // Act

        $actual = resolve(RegenerateTwoFactorRecoveryCodesAction::class)->execute($user);

        // Assert

        $this->assertNull($actual);
    }

    public function test_it_regenerates_recovery_codes_for_confirmed_user(): void
    {
        // Arrange

        $user = User::factory()->create();

        $oldCodes = ['old-code-1', 'old-code-2'];

        $twoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'recovery_codes' => $oldCodes,
            ]);

        $expectedRecoveryCodes = $this->stubRecoveryCodesGeneration();

        // Act

        $actual = resolve(RegenerateTwoFactorRecoveryCodesAction::class)->execute($user);

        // Assert

        $this->assertNotNull($actual);
        $this->assertCount(8, $actual);
        $this->assertEquals($expectedRecoveryCodes, $actual);

        $this->assertEquals($expectedRecoveryCodes, $twoFactorAuth->refresh()->recovery_codes);
        $this->assertNotEquals($oldCodes, $actual);
    }

    /*
     * Helpers.
     */

    /**
     * @return string[]
     */
    public function stubRecoveryCodesGeneration(): array
    {
        $expectedRecoveryCodePairs = Collection::range(1, 8)
            ->map(fn (): array => [
                Str::lower(fake()->unique()->lexify('?????')),
                Str::lower(fake()->unique()->lexify('?????')),
            ]);

        $expectedRecoveryCodes = $expectedRecoveryCodePairs
            ->map(fn (array $pair): string => "{$pair[0]}-{$pair[1]}")
            ->all();

        $randomStringSeeder = $expectedRecoveryCodePairs->flatten();

        Str::createRandomStringsUsing(fn () => $randomStringSeeder->shift());

        return $expectedRecoveryCodes;
    }
}
