<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\VerifyTwoFactorAuthCodeAction;
use App\Modules\Auth\DataTransferObjects\VerifyTwoFactorAuthCodeData;
use App\Modules\Auth\Exceptions\TwoFactorAuthSecretIsUnsetException;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(VerifyTwoFactorAuthCodeAction::class)]
#[CoversClass(VerifyTwoFactorAuthCodeData::class)]
#[CoversClass(TwoFactorAuthSecretIsUnsetException::class)]
#[Group('authentication')]
#[Group('2fa')]
class VerifyTwoFactorAuthCodeActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_confirms_2fa_with_valid_code(): void
    {
        // Arrange

        $user = User::factory()->create();

        $userTwoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->create([
                'secret' => $secret = Hash::make(fake()->password()),
                'confirmed_at' => null,
            ]);

        $twoFactorAuthManagerMock = $this->mock(TwoFactorAuthManager::class);

        $code = fake()->numerify('??????');

        $data = VerifyTwoFactorAuthCodeData::fromUser($user, $code);

        // Anticipate

        $twoFactorAuthManagerMock->expects('verify')->andReturnTrue();

        // Act

        $actual = resolve(VerifyTwoFactorAuthCodeAction::class)
            ->execute($data);

        // Assert

        $this->assertTrue($actual);

        $this->assertEquals(
            now(),
            $userTwoFactorAuth->fresh()->confirmed_at,
        );

        $this->assertManagerWasTriggeredProperly($secret, $code);
    }

    public function test_it_returns_false_with_invalid_code(): void
    {
        // Arrange

        $user = User::factory()->create();

        $userTwoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->create([
                'secret' => $secret = Hash::make(fake()->password()),
                'confirmed_at' => null,
            ]);

        $twoFactorAuthManagerMock = $this->mock(TwoFactorAuthManager::class);

        $code = fake()->numerify('??????');

        $data = VerifyTwoFactorAuthCodeData::fromUser($user, $code);

        // Anticipate

        $twoFactorAuthManagerMock->expects('verify')->andReturnFalse();

        // Act

        $actual = resolve(VerifyTwoFactorAuthCodeAction::class)->execute($data);

        // Assert

        $this->assertFalse($actual);

        $this->assertNull(
            $userTwoFactorAuth->fresh()->confirmed_at,
        );

        $this->assertManagerWasTriggeredProperly($secret, $code);
    }

    public function test_it_throws_exception_when_secret_is_not_set(): void
    {
        // Arrange

        $user = User::factory()->create();

        $twoFactorAuth = UserTwoFactorAuth::factory()
            ->for($user)
            ->create(['secret' => '']);

        $data = VerifyTwoFactorAuthCodeData::fromUser($user, 'any-code');

        // Act

        try {
            resolve(VerifyTwoFactorAuthCodeAction::class)->execute($data);

            $this->fail('It should throw exception');
        } catch (TwoFactorAuthSecretIsUnsetException) {
        }

        // Assert

        $this->assertNull(
            $twoFactorAuth->fresh()->confirmed_at,
        );
    }

    public function test_it_throws_exception_when_two_factor_auth_is_not_enabled(): void
    {
        // Arrange

        $user = User::factory()->create();

        $verifyTwoFactorAuthCodeData = VerifyTwoFactorAuthCodeData::fromUser($user, 'any-code');

        // Assert

        $this->expectException(TwoFactorAuthSecretIsUnsetException::class);

        // Act

        resolve(VerifyTwoFactorAuthCodeAction::class)->execute($verifyTwoFactorAuthCodeData);
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
