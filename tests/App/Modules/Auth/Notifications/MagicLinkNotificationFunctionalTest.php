<?php

namespace Tests\App\Modules\Auth\Notifications;

use App\Modules\Auth\Notifications\MagicLinkNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(MagicLinkNotification::class)]
#[Group('authentication')]
class MagicLinkNotificationFunctionalTest extends FunctionalTestCase
{
    public function test_it_dispatches_via_mail(): void
    {
        // Arrange

        $user = User::factory()->make();

        $notification = new MagicLinkNotification(
            token: $token = fake()->uuid(),
            expirationInMinutes: $expiration = 15,
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

        Config::set('webhooks.frontend.redirects.magic_link_url', $baseUrl = 'https://app.test/magic-link');

        $user = User::factory()->make();

        $notification = new MagicLinkNotification(
            token: $token = fake()->uuid(),
            expirationInMinutes: $expiration = 15,
        );

        // Act

        $data = $notification->toMail($user)->toArray();

        // Assert

        $expectedActionUrl = sprintf('%s/?token=%s&email=%s', $baseUrl, $token, urlencode((string) $user->email));

        $this->assertEquals(
            [
                'level' => 'info',
                'subject' => 'Your Magic Login Link',
                'greeting' => 'Ready to log back in?',
                'salutation' => null,
                'introLines' => [
                    'Click the button below to log in to your account instantly, no password required.',
                ],
                'outroLines' => [
                    "This secure login link will expire in {$expiration} minutes.",
                    'If you didn’t request this link, you can safely ignore this email; your account remains secure.',
                ],
                'actionText' => 'Log In Now',
                'actionUrl' => $expectedActionUrl,
                'displayableActionUrl' => $expectedActionUrl,
            ],
            $data,
        );
    }
}
