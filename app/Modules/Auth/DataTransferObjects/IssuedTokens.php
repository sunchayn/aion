<?php

namespace App\Modules\Auth\DataTransferObjects;

final readonly class IssuedTokens
{
    public function __construct(
        public string $authToken,
        public string $refreshToken,
    ) {}
}
