<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\VerifyEmailAction;
use App\Modules\Auth\DataTransferObjects\VerifyEmailData;
use App\Modules\Auth\Exceptions\InvalidEmailVerificationLinkException;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(VerifyEmailAction::class)]
#[CoversClass(VerifyEmailData::class)]
#[Group('authentication')]
final class VerifyEmailActionFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([Verified::class]);
    }

    public function test_it_verifies_email_successfully(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        // Act

        $hash = sha1((string) $user->getEmailForVerification());

        resolve(VerifyEmailAction::class)->execute(new VerifyEmailData($user, $hash));

        // Assert

        $this->assertNotNull($user->fresh()->email_verified_at);

        Event::assertDispatched(
            Verified::class,
            fn (Verified $event): bool => $event->user->id === $user->id,
        );
    }

    public function test_it_throws_exception_if_hash_is_invalid(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        // Anticipate

        $this->expectException(InvalidEmailVerificationLinkException::class);

        // Act

        resolve(VerifyEmailAction::class)->execute(new VerifyEmailData($user, 'invalid-hash'));
    }

    public function test_it_does_not_dispatch_event_if_already_verified(): void
    {
        // Arrange

        $user = User::factory()->create();

        $hash = sha1((string) $user->getEmailForVerification());

        // Act

        resolve(VerifyEmailAction::class)
            ->execute(
                new VerifyEmailData($user, $hash),
            );

        // Assert

        Event::assertNotDispatched(Verified::class);
    }
}
