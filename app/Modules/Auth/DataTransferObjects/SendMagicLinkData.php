<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Shared\Models\User;

final readonly class SendMagicLinkData
{
    public function __construct(
        public ?User $user,
    ) {}

    public static function fromEmail(string $email): self
    {
        return new self(
            user: User::query()
                ->where('email', $email)
                ->first(),
        );
    }
}
