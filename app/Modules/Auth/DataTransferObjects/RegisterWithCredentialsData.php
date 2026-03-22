<?php

namespace App\Modules\Auth\DataTransferObjects;

final readonly class RegisterWithCredentialsData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
