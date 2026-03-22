<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\ResendEmailVerificationAction;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ResendEmailVerificationAction::class)]
#[Group('authentication')]
final class ResendEmailVerificationActionFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_it_sends_notification_if_unverified(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        // Act

        $result = resolve(ResendEmailVerificationAction::class)->execute($user);

        // Assert

        $this->assertTrue($result);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_it_returns_false_if_verified(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $actual = resolve(ResendEmailVerificationAction::class)->execute($user);

        // Assert

        $this->assertFalse($actual);

        Notification::assertNothingSent();
    }
}
