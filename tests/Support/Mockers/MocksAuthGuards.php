<?php

namespace Tests\Support\Mockers;

use Illuminate\Auth\AuthManager;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

/** @mixin TestCase */
trait MocksAuthGuards
{
    /**
     * @return array{guardName: string, guard: JWTGuard}
     */
    protected function makeDummyStatelessGuard(): array
    {
        $guardName = '::stateless_guard::';

        $authManagerMock = $this->mock(AuthManager::class);

        $guard = resolve(
            JWTGuard::class,
            ['provider' => new AuthManager($this->app)->createUserProvider('users')],
        );

        $authManagerMock
            ->shouldReceive('guard')
            ->once()
            ->with($guardName)
            ->andReturn($guard);

        return [
            'guardName' => $guardName,
            'guard' => $guard,
        ];
    }
}
