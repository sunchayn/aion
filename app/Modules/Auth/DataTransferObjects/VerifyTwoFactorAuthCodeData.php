<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;

final readonly class VerifyTwoFactorAuthCodeData
{
    public function __construct(
        public ?UserTwoFactorAuth $twoFactorAuth,
        public string $code,
    ) {}

    public static function fromUser(User $user, string $code): self
    {
        return new self($user->twoFactorAuth, $code);
    }
}
