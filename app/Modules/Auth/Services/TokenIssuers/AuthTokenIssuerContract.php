<?php

namespace App\Modules\Auth\Services\TokenIssuers;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Shared\Models\User;

interface AuthTokenIssuerContract
{
    /**
     * Set the guard context to be used for subsequent token operations.
     *
     * @param  string  $guard  The guard name to use
     */
    public function useGuard(string $guard): self;

    /**
     * Issue an auth token for the given user and dispatch the Login event.
     * Returns null when the auth driver does not issue tokens (e.g. session/cookie).
     */
    public function issue(User $user): IssuedTokens;

    /**
     * Exchange an existing (or recently-expired) token for a fresh one.
     * Returns null when the driver does not support token rotation.
     */
    public function refresh(string $token): ?IssuedTokens;

    /**
     * Resolve a User from an already-issued token.
     * Returns null when the token is invalid or the user no longer exists.
     */
    public function userFromToken(string $token): ?User;
}
