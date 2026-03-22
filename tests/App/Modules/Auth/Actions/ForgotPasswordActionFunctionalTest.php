<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\ForgotPasswordAction;
use App\Modules\Auth\DataTransferObjects\ForgotPasswordData;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ForgotPasswordAction::class)]
#[CoversClass(ForgotPasswordData::class)]
#[Group('authentication')]
class ForgotPasswordActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_sends_password_reset_link(): void
    {
        // Arrange

        Notification::fake();

        $user = User::factory()->create();

        Password::shouldReceive('createToken')
            ->once()
            ->withArgs(fn (User $userArg) => $user->is($userArg))
            ->andReturn($resetToken = fake()->uuid());

        // Act

        resolve(ForgotPasswordAction::class)
            ->execute(
                ForgotPasswordData::fromEmail($user->email),
            );

        // Assert

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($resetToken): true {
                $this->assertEquals(
                    $resetToken,
                    $notification->token,
                );

                return true;
            }
        );
    }

    public function test_it_discard_request_if_user_is_not_found(): void
    {
        // Arrange

        Notification::fake();

        Password::shouldReceive('broker')->never();

        // Act

        resolve(ForgotPasswordAction::class)
            ->execute(
                ForgotPasswordData::fromEmail(
                    email: 'noop@email.com'
                ),
            );

        // Assert

        Notification::assertNothingSent();
    }
}
