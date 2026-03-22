<?php

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Shared\Models\User;

final readonly class LoginWithCredentialsData
{
    public function __construct(
        public ?User $user,
        public ?string $password,
        public ?string $guard = null,
        public bool $remember = false,
    ) {}

    public static function fromEmail(string $email, ?string $password, ?string $guard = null, bool $remember = false): self
    {
        return new self(
            user: User::query()->where('email', $email)->first(),
            password: $password,
            guard: $guard,
            remember: $remember,
        );
    }
}
