<?php

declare(strict_types=1);

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\DisableTwoFactorAuthAction;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(DisableTwoFactorAuthAction::class)]
#[Group('authentication')]
#[Group('2fa')]
class DisableTwoFactorAuthActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_disables_two_factor_auth(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create();

        $action = resolve(DisableTwoFactorAuthAction::class);

        // Act

        $action->execute($user);

        // Assert

        $this->assertDatabaseMissing(
            'user_two_factor_auth',
            [
                'user_id' => $user->id,
            ],
        );

        $this->assertNull($user->refresh()->twoFactorAuth);
    }
}
