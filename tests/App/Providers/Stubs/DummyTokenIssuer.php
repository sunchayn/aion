<?php

namespace Tests\App\Providers\Stubs;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Str;

class DummyTokenIssuer implements AuthTokenIssuerContract
{
    public function useGuard(string $guard): AuthTokenIssuerContract
    {
        return $this;
    }

    public function issue(User $user): IssuedTokens
    {
        return new IssuedTokens(
            authToken: Str::uuid(),
            refreshToken: Str::uuid(),
        );
    }

    public function refresh(string $token): ?IssuedTokens
    {
        return new IssuedTokens(
            authToken: Str::uuid(),
            refreshToken: Str::uuid(),
        );
    }

    public function userFromToken(string $token): ?User
    {
        return null;
    }
}
