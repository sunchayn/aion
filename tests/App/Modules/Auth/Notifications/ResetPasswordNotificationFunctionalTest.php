<?php

namespace Tests\App\Modules\Auth\Notifications;

use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ResetPasswordNotification::class)]
#[Group('authentication')]
class ResetPasswordNotificationFunctionalTest extends FunctionalTestCase
{
    public function test_it_dispatches_via_mail(): void
    {
        // Arrange

        $user = User::factory()->make();

        $notification = new ResetPasswordNotification(
            token: $token = fake()->uuid(),
        );

        // Act & Assert

        $this->assertEquals(
            [
                'mail',
            ],
            $notification->via($user),
        );
    }

    public function test_it_generates_correct_mail_content(): void
    {
        // Arrange

        Config::set('webhooks.frontend.redirects.password_reset_url', $baseUrl = 'https://app.test/password-reset');

        Config::set('auth.passwords.users.expire', $expiration = 60);

        $user = User::factory()->make([]);

        $notification = new ResetPasswordNotification(
            token: $token = fake()->uuid(),
        );

        // Act

        $data = $notification->toMail($user)->toArray();

        // Assert

        $expectedActionUrl = sprintf('%s/?token=%s&email=%s', $baseUrl, $token, urlencode((string) $user->email));

        $this->assertEquals(
            [
                'level' => 'info',
                'subject' => 'Reset Your Password',
                'greeting' => null,
                'salutation' => null,
                'introLines' => [
                    'You are receiving this email because we received a password reset request for your account.',
                ],
                'outroLines' => [
                    "This password reset link will expire in {$expiration} minutes.",
                    'If you did not request a password reset, no further action is required.',
                ],
                'actionText' => 'Reset Password',
                'actionUrl' => $expectedActionUrl,
                'displayableActionUrl' => $expectedActionUrl,
            ],
            $data,
        );
    }
}
