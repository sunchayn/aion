<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\SendMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\SendMagicLinkData;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Notifications\MagicLinkNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(SendMagicLinkAction::class)]
#[CoversClass(SendMagicLinkData::class)]
#[Group('authentication')]
class SendMagicLinkActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_sends_magic_link_to_user(): void
    {
        // Arrange

        Notification::fake();

        $user = User::factory()->create();

        $fakeToken = fake()->uuid();

        Str::createRandomStringsUsing(fn () => $fakeToken);

        // Act

        resolve(SendMagicLinkAction::class)
            ->execute(
                SendMagicLinkData::fromEmail($user->email),
            );

        // Assert

        $this->assertDatabaseHas(
            'magic_links',
            [
                'user_id' => $user->id,
                'used_at' => null,
                'expires_at' => now()->addMinutes(SendMagicLinkAction::EXPIRATION_IN_MINUTES),
            ],
        );

        // The stored token must be a bcrypt hash, not the plain token.
        $storedToken = MagicLink::query()->where('user_id', $user->id)->value('token');
        $this->assertTrue(Hash::isHashed($storedToken));
        $this->assertTrue(Hash::check($fakeToken, $storedToken));

        Notification::assertSentTo(
            $user,
            MagicLinkNotification::class,
            function (MagicLinkNotification $notification) use ($fakeToken): true {
                $this->assertEquals(
                    $fakeToken,
                    $notification->token,
                );

                return true;
            }
        );
    }

    public function test_it_does_nothing_if_user_not_found(): void
    {
        // Arrange

        Notification::fake();

        $sendMagicLinkData = SendMagicLinkData::fromEmail('nonexistent@example.com');

        // Act

        resolve(SendMagicLinkAction::class)->execute($sendMagicLinkData);

        // Assert

        $this->assertDatabaseCount('magic_links', 0);

        Notification::assertNothingSent();
    }
}
