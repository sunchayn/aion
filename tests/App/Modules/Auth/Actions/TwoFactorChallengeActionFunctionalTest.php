<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\TwoFactorChallengeAction;
use App\Modules\Auth\DataTransferObjects\TwoFactorChallengeData;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(TwoFactorChallengeAction::class)]
#[CoversClass(TwoFactorChallengeData::class)]
#[Group('authentication')]
#[Group('2fa')]
class TwoFactorChallengeActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_returns_true_when_2fa_is_not_enabled(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)->execute(TwoFactorChallengeData::fromUser($user, '::code::'));

        // Assert

        $this->assertTrue($actual);
    }

    public function test_it_returns_true_if_2fa_is_not_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->unconfirmed()
            ->create();

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)
            ->execute(
                TwoFactorChallengeData::fromUser($user, '::code::'),
            );

        // Assert

        $this->assertTrue($actual);
    }

    public function test_it_verifies_code_if_2fa_is_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'secret' => $secret = Hash::make(fake()->password()),
            ]);

        $code = fake()->numerify('??????');

        $data = TwoFactorChallengeData::fromUser($user, $code);

        // Anticipate

        $this->mock(TwoFactorAuthManager::class, function (MockInterface $mock): void {
            $mock->expects('verify')->andReturnTrue();
        });

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)->execute($data);

        // Assert

        $this->assertTrue($actual);

        $this->assertManagerWasTriggeredProperly($secret, $code);
    }

    public function test_it_returns_false_for_invalid_code_when_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'secret' => $secret = Hash::make(fake()->password()),
            ]);

        $code = fake()->numerify('??????');

        $data = TwoFactorChallengeData::fromUser($user, $code);

        // Anticipate

        $this->mock(TwoFactorAuthManager::class, function (MockInterface $mock): void {
            $mock->expects('verify')->andReturnFalse();
        });

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)->execute($data);

        // Assert

        $this->assertFalse($actual);

        $this->assertManagerWasTriggeredProperly($secret, $code);
    }

    public function test_it_verifies_recovery_code_if_2fa_is_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        $recoveryCodes = ['code-1', 'code-2'];

        $twoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'recovery_codes' => $recoveryCodes,
            ]);

        $data = TwoFactorChallengeData::fromUser($user, null, 'code-1');

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)->execute($data);

        // Assert

        $this->assertTrue($actual);

        $this->assertEquals(['code-2'], $twoFactorAuth->refresh()->recovery_codes);
    }

    public function test_it_returns_false_for_invalid_recovery_code_when_confirmed(): void
    {
        // Arrange

        $user = User::factory()->create();

        $recoveryCodes = ['code-1', 'code-2'];

        $twoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'recovery_codes' => $recoveryCodes,
            ]);

        $data = TwoFactorChallengeData::fromUser($user, null, 'invalid-code');

        // Act

        $actual = resolve(TwoFactorChallengeAction::class)->execute($data);

        // Assert

        $this->assertFalse($actual);

        $this->assertEquals($recoveryCodes, $twoFactorAuth->refresh()->recovery_codes);
    }

    public function test_it_throws_assertion_error_when_no_code_provided(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->confirmed()
            ->create([
                'secret' => Hash::make(fake()->password()),
            ]);

        // Anticipate

        $this->expectException(InvalidArgumentException::class);

        // Act

        TwoFactorChallengeData::fromUser($user);
    }

    /*
     * Asserts.
     */

    public function assertManagerWasTriggeredProperly(
        string $secret,
        string $code,
    ): void {
        resolve(TwoFactorAuthManager::class)
            ->shouldHaveReceived('verify')
            ->withArgs(function (string $secretArg, string $codeArg) use ($secret, $code): bool {
                $this->assertEquals(
                    $secret,
                    $secretArg,
                );

                $this->assertEquals(
                    $codeArg,
                    $code,
                );

                return true;
            })
            ->once();
    }
}
