<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Shared\Models\User;

final readonly class LoginData
{
    public string $guard;

    public function __construct(
        public User $user,
        ?string $guard = null,
        public bool $remember = false,
        public bool $skipTwoFactor = false,
    ) {
        $this->guard = $guard ?? config()->string('auth.defaults.guard', 'web');
    }
}
