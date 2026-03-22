<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Auth\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;

/** @final */
class RefreshTokenAction
{
    public function __construct(
        private readonly AuthTokenIssuerContract $tokenIssuer,
        private readonly Dispatcher $dispatcher,
        private readonly Repository $config,
    ) {}

    public function execute(string $refreshToken): TokenLoginResponse
    {
        $guard = $this->config->get('auth.defaults.guard', 'web');

        $this->tokenIssuer->useGuard($guard);

        $issuedTokens = $this->tokenIssuer->refresh($refreshToken);

        if (! $issuedTokens instanceof IssuedTokens) {
            throw InvalidRefreshTokenException::becauseTokenIsInvalid();
        }

        $user = $this->tokenIssuer->userFromToken($issuedTokens->authToken);

        if (! $user instanceof User) {
            throw InvalidRefreshTokenException::becauseTokenIsInvalid();
        }

        $this->dispatcher->dispatch(
            new Login(
                guard: $guard,
                user: $user,
                remember: false,
            ),
        );

        return new TokenLoginResponse(
            user: $user,
            authToken: $issuedTokens->authToken,
            refreshToken: $issuedTokens->refreshToken,
        );
    }
}
