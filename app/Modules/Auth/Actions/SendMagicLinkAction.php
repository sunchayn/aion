<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\SendMagicLinkData;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Notifications\MagicLinkNotification;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @final */
class SendMagicLinkAction
{
    public const int EXPIRATION_IN_MINUTES = 15;

    public function execute(SendMagicLinkData $data): void
    {
        if (! $data->user instanceof User) {
            // When no user is matching the email we just discard the magic link request.
            // The requester shouldn't be informed about this (a.k.a sending 404).
            return;
        }

        $token = $this->createToken();

        $this->persistMagicLinkInDB($data->user, $token);

        $data->user->notify(
            new MagicLinkNotification($token, self::EXPIRATION_IN_MINUTES),
        );
    }

    public function persistMagicLinkInDB(User $user, string $token): void
    {
        MagicLink::query()->create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->toImmutable()->addMinutes(self::EXPIRATION_IN_MINUTES),
        ]);
    }

    public function createToken(): string
    {
        return Str::random(64);
    }
}
