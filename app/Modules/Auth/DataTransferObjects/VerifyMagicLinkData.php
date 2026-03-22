<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class VerifyMagicLinkData
{
    public function __construct(
        public User $user,
        public ?MagicLink $magicLink,
        public ?string $guard = null,
        public bool $remember = false,
    ) {}

    public static function fromToken(string $token, string $email, bool $remember = false): self
    {
        $user = User::query()->where('email', $email)->first();

        $magicLink = $user
            ? MagicLink::query()
                ->with('user')
                ->where('user_id', $user->id)
                ->valid()
                ->get()
                ->first(fn (MagicLink $link): bool => Hash::check($token, $link->token))
            : null;

        return new self(
            user: $user ?? new User,
            magicLink: $magicLink,
            remember: $remember,
        );
    }
}
