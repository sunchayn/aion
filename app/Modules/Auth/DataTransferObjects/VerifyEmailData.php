<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Shared\Models\User;

final readonly class VerifyEmailData
{
    public function __construct(
        public User $user,
        public string $hash,
    ) {}
}
