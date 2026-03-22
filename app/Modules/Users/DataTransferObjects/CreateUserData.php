<?php

namespace App\Modules\Users\DataTransferObjects;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
