<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\EnableTwoFactorAuthAction;
use App\Modules\Auth\DataTransferObjects\TwoFactorSetupPayload;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(EnableTwoFactorAuthAction::class)]
#[CoversClass(TwoFactorSetupPayload::class)]
#[Group('authentication')]
#[Group('2fa')]
class EnableTwoFactorAuthActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_enables_2fa_for_user(): void
    {
        // Arrange

        $user = User::factory()->create();

        $this->mockTwoFactorAuthProvider(
            secret: $secret = fake()->uuid(),
            qrCodeUrl: $qrCodeUrl = fake()->url(),
        );

        $expectedRecoveryCodes = $this->stubRecoveryCodesGeneration();

        // Act

        $actual = resolve(EnableTwoFactorAuthAction::class)->execute($user);

        // Assert

        $this->assertEquals($secret, $actual->secret);
        $this->assertEquals($qrCodeUrl, $actual->qrCodeUrl);
        $this->assertCount(8, $actual->recoveryCodes);

        $userTwoFactorAuth = UserTwoFactorAuth::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($userTwoFactorAuth);
        $this->assertNull($userTwoFactorAuth->confirmed_at);
        $this->assertEquals($secret, $userTwoFactorAuth->secret);
        $this->assertEquals($expectedRecoveryCodes, $userTwoFactorAuth->recovery_codes);
    }

    public function test_it_overwrites_existent_2fa_for_user_without_crashing(): void
    {
        // Arrange

        $user = User::factory()->create();

        UserTwoFactorAuth::factory()
            ->for($user)
            ->create();

        $this->mockTwoFactorAuthProvider(
            secret: $secret = fake()->uuid(),
            qrCodeUrl: $qrCodeUrl = fake()->url(),
        );

        $expectedRecoveryCodes = $this->stubRecoveryCodesGeneration();

        // Act

        $actual = resolve(EnableTwoFactorAuthAction::class)->execute($user);

        // Assert

        $this->assertEquals($secret, $actual->secret);
        $this->assertEquals($qrCodeUrl, $actual->qrCodeUrl);
        $this->assertCount(8, $actual->recoveryCodes);

        $userTwoFactorAuthAll = UserTwoFactorAuth::query()->where('user_id', $user->id)->get();

        $this->assertCount(1, $userTwoFactorAuthAll);

        $userTwoFactorAuth = $userTwoFactorAuthAll->first();

        $this->assertNull($userTwoFactorAuth->confirmed_at);
        $this->assertEquals($secret, $userTwoFactorAuth->secret);
        $this->assertEquals($expectedRecoveryCodes, $userTwoFactorAuth->recovery_codes);
    }

    /*
     * Mocks.
     */

    public function mockTwoFactorAuthProvider(string $secret, string $qrCodeUrl): void
    {
        $this->mock(TwoFactorAuthManager::class, function (MockInterface $mock) use ($secret, $qrCodeUrl): void {
            $mock->expects('generateSecretKey')->andReturn($secret);
            $mock->expects('generateQrCode')->andReturn($qrCodeUrl);
        });
    }

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
