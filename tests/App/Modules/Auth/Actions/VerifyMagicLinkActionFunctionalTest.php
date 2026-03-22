<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\VerifyMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\VerifyMagicLinkData;
use App\Modules\Auth\Exceptions\UnusableMagicLinkException;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(VerifyMagicLinkAction::class)]
#[CoversClass(VerifyMagicLinkData::class)]
#[CoversClass(UnusableMagicLinkException::class)]
#[Group('authentication')]
class VerifyMagicLinkActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_logs_in_user_with_valid_magic_link(): void
    {
        // Arrange

        $user = User::factory()->create();
        $plainToken = fake()->uuid();

        $magicLink = MagicLink::factory()
            ->for($user)
            ->create([
                'token' => Hash::make($plainToken),
                'used_at' => null,
                'expires_at' => now()->addMinutes(10),
            ]);

        $dummyLoggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: 'token-123',
        );

        $loginActionMock = $this->mock(LoginAction::class);

        $verifyMagicLinkData = new VerifyMagicLinkData(
            user: $user,
            magicLink: $magicLink->load('user'),
        );

        // Anticipate

        $loginActionMock->expects('execute')->andReturn($dummyLoggedInUser);

        // Act

        $actualResponse = resolve(VerifyMagicLinkAction::class)
            ->execute($verifyMagicLinkData);

        // Assert

        $this->assertEquals($user->id, $actualResponse->user->id);
        $this->assertNotNull($magicLink->fresh()->used_at);

        $loginActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (LoginData $dataArg) use ($user): bool {
                $this->assertTrue(
                    $dataArg->user->is($user),
                );

                return true;
            });
    }

    public function test_it_throws_exception_when_magic_link_was_not_found_for_user(): void
    {
        // Arrange

        $user = new User;

        $magicLink = null;

        // Anticipate

        $this->expectException(UnusableMagicLinkException::class);

        // Act

        resolve(VerifyMagicLinkAction::class)->execute(new VerifyMagicLinkData($user, $magicLink));
    }

    public function test_it_throws_exception_when_magic_link_was_already_used(): void
    {
        // Arrange

        $user = User::factory()->create();

        $usedLink = MagicLink::factory()
            ->used()
            ->for($user)
            ->create();

        // Anticipate

        $this->expectException(UnusableMagicLinkException::class);

        // Act

        resolve(VerifyMagicLinkAction::class)->execute(new VerifyMagicLinkData($user, $usedLink));
    }

    public function test_it_throws_exception_when_magic_link_is_expired(): void
    {
        // Arrange

        $user = User::factory()->create();

        $expiredLink = MagicLink::factory()
            ->for($user)
            ->expired()
            ->create(['used_at' => null]);

        // Act

        try {
            resolve(VerifyMagicLinkAction::class)->execute(new VerifyMagicLinkData($user, $expiredLink));

            $this->fail('Expired link should throw exception');
        } catch (UnusableMagicLinkException) {
        }

        // Assert

        $this->assertNull($expiredLink->fresh()?->used_at);
    }
}
