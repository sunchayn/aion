<?php

namespace App\Modules\Auth\Services\TokenIssuers;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Shared\Models\User;

final class NullTokenIssuer implements AuthTokenIssuerContract
{
    public function useGuard(string $guard): self
    {
        return $this;
    }

    public function issue(User $user): IssuedTokens
    {
        return new IssuedTokens('', '');
    }

    public function refresh(string $token): ?IssuedTokens
    {
        return null;
    }

    public function userFromToken(string $token): ?User
    {
        return null;
    }
}
