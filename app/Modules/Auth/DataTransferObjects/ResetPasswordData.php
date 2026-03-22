<?php

namespace App\Modules\Auth\DataTransferObjects;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $token,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ];
    }
}
