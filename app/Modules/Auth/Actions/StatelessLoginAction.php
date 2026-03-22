<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use Illuminate\Auth\Events\Login;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Contracts\Events\Dispatcher;

/** @final */
class StatelessLoginAction
{
    /** @param iterable<callable(LoginData): ?TokenLoginResponse> $beforeLoginHooks */
    public function __construct(
        private readonly AuthTokenIssuerContract $tokenIssuer,
        private readonly Dispatcher $dispatcher,
        #[Tag('hooks.login.before')]
        private readonly iterable $beforeLoginHooks,
    ) {}

    public function execute(LoginData $data): TokenLoginResponse
    {
        $this->tokenIssuer->useGuard($data->guard);

        foreach ($this->beforeLoginHooks as $hook) {
            $result = $hook($data);

            if ($result instanceof TokenLoginResponse) {
                return $result;
            }
        }

        $issuedTokens = $this->tokenIssuer->issue($data->user);

        $this->dispatcher->dispatch(
            new Login(
                guard: $data->guard,
                user: $data->user,
                remember: false,
            ),
        );

        return new TokenLoginResponse(
            user: $data->user,
            authToken: $issuedTokens->authToken,
            refreshToken: $issuedTokens->refreshToken,
            twoFactor: false,
        );
    }
}
