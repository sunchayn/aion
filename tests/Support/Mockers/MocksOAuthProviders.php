<?php

namespace Tests\Support\Mockers;

use App\Modules\Auth\OAuthProviders\OAuthProviderBase;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/** @mixin TestCase */
trait MocksOAuthProviders
{
    protected function mockSocialiteUser(
        string $id,
        ?string $email = null,
        ?string $name = null,
        ?string $avatar = null,
        ?string $token = null,
        ?string $refreshToken = null,
        ?int $expiresIn = null,
    ): MockInterface {
        $socialiteUserMock = $this->mock(SocialiteUser::class);

        $socialiteUserMock->allows('getId')->andReturn($id);
        $socialiteUserMock->allows('getEmail')->andReturn($email ?? fake()->safeEmail());
        $socialiteUserMock->allows('getName')->andReturn($name ?? fake()->name());
        $socialiteUserMock->allows('getAvatar')->andReturn($avatar);

        $socialiteUserMock->token = $token;
        $socialiteUserMock->refreshToken = $refreshToken;
        $socialiteUserMock->expiresIn = $expiresIn;

        return $socialiteUserMock;
    }

    protected function mockOAuthProviderToReturn(?MockInterface $socialiteUserMock, mixed $provider = null): MockInterface
    {
        $oAuthProviderMock = $this->mock(OAuthProviderBase::class);
        $oAuthProviderMock->allows('fetchUser')->andReturn($socialiteUserMock);

        $this->mock(
            ProvidersFactory::class,
            function (MockInterface $mock) use ($oAuthProviderMock, $provider): void {
                $expectation = $mock->allows('make');

                if ($provider !== null) {
                    $expectation->with($provider);
                }

                $expectation->andReturn($oAuthProviderMock);
            },
        );

        return $oAuthProviderMock;
    }
}
