<?php

namespace App\Modules\Auth\Services\TokenIssuers;

use App\Modules\Auth\DataTransferObjects\IssuedTokens;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository;
use InvalidArgumentException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

/** @final */
class JwtTokenIssuer implements AuthTokenIssuerContract
{
    private JWTGuard $guard;

    public function __construct(
        private readonly AuthManager $authManager,
        private readonly Repository $config,
    ) {}

    public function useGuard(string $guard): self
    {
        $guard = $this->authManager->guard($guard);

        if (! $guard instanceof JWTGuard) {
            throw new InvalidArgumentException('The provided guard must be of type <'.JWTGuard::class.'>');
        }

        $this->guard = $guard;

        return $this;
    }

    public function issue(User $user): IssuedTokens
    {
        $authToken = $this->generateTokenWith(
            $user,
            claims: ['type' => 'access'],
        );

        $refreshToken = $this->generateTokenWith(
            $user,
            claims: ['type' => 'refresh'],
            ttl: $this->config->integer('auth.jwt.refresh_token_ttl'),
        );

        return new IssuedTokens(
            authToken: $authToken,
            refreshToken: $refreshToken,
        );
    }

    public function refresh(string $token): ?IssuedTokens
    {
        try {
            $payload = $this->guard->setToken($token)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return null;
            }

            $user = $this->guard->user();

            if (! $user instanceof User) {
                return null;
            }

            $this->guard->invalidate(forceForever: true);

            return $this->issue($user);
        } catch (JWTException) {
            return null;
        }
    }

    public function userFromToken(string $token): ?User
    {
        /** @var User|null */
        return $this->guard->setToken($token)->user();
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function generateTokenWith(User $user, array $claims, ?int $ttl = null): string
    {
        if ($ttl !== null) {
            $this->guard->factory()->setTTL($ttl);
        }

        return $this->guard->claims($claims)->login($user);
    }
}
