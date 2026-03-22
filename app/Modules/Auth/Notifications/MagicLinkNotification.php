<?php

namespace App\Modules\Auth\Notifications;

use App\Modules\Shared\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public int $expirationInMinutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = config('webhooks.frontend.redirects.magic_link_url').'/?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Your Magic Login Link')
            ->greeting('Ready to log back in?')
            ->line('Click the button below to log in to your account instantly, no password required.')
            ->action('Log In Now', $url)
            ->line("This secure login link will expire in {$this->expirationInMinutes} minutes.")
            ->line('If you didn’t request this link, you can safely ignore this email; your account remains secure.');
    }
}
