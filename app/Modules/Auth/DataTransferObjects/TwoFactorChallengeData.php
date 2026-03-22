<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;

final readonly class TwoFactorChallengeData
{
    public function __construct(
        public ?UserTwoFactorAuth $twoFactorAuth,
        public ?string $code,
        public ?string $recoveryCode = null,
    ) {
        if ($code === null && $this->recoveryCode === null) {
            throw new \InvalidArgumentException('At least a 2fa code or a recovery code must be provided');
        }

        if ($code !== null && $this->recoveryCode !== null) {
            throw new \InvalidArgumentException('A 2fa code and a recovery code cannot be provided at the same time.');
        }
    }

    public static function fromUser(User $user, ?string $code = null, ?string $recoveryCode = null): self
    {
        return new self($user->twoFactorAuth, $code, $recoveryCode);
    }
}
