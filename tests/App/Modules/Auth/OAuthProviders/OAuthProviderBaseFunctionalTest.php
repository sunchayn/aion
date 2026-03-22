<?php

namespace Tests\App\Modules\Auth\OAuthProviders;

use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use App\Modules\Auth\OAuthProviders\OAuthProviderBase;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Exception;
use Laravel\Socialite\Two\AbstractProvider as AbstractSocialiteProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(OAuthProviderBase::class)]
#[Group('authentication')]
#[Group('oauth')]
class OAuthProviderBaseFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenSocialiteIsNotAvailable();
    }

    public function test_it_redirects_to_provider_with_encrypted_state(): void
    {
        // Arrange

        $operationType = OperationTypeEnum::Auth;

        $baseProviderMock = $this->createBaseProviderStub();

        $baseProviderMock
            ->socialite()
            ->allows('stateless')
            ->andReturnSelf();

        $baseProviderMock
            ->socialite()
            ->expects('with')
            ->withAnyArgs()
            ->andReturnSelf();

        $baseProviderMock
            ->socialite()
            ->expects('redirect')
            ->once()
            ->andReturn(redirect('https://auth-url'));

        // Act

        $response = $baseProviderMock->redirectToProvider($operationType);

        // Assert

        $this->assertEquals('https://auth-url', $response->getTargetUrl());

        $baseProviderMock
            ->socialite()
            ->shouldHaveReceived('with')
            ->withArgs(function (array $args) use ($operationType): true {
                $this->assertEquals(
                    ['operation_type' => $operationType->value],
                    json_decode(base64_decode((string) $args['state']), true),
                );

                $this->assertEquals(
                    'param',
                    $args['extra'],
                );

                return true;
            })
            ->once();
    }

    public function test_it_fetches_user_successfully(): void
    {
        // Arrange

        $token = OAuthToken::accessToken('valid-token');

        $baseProviderMock = $this->createBaseProviderStub();

        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = '123';
        $socialiteUser->email = 'test@example.com';

        $baseProviderMock->expects('performUserFetch')->withAnyArgs()->andReturn($socialiteUser);

        // Act

        $actualUser = $baseProviderMock->fetchUser($token);

        // Assert

        $this->assertSame($socialiteUser, $actualUser);

        $baseProviderMock
            ->shouldHaveReceived('performUserFetch')
            ->withArgs(function (OAuthToken $tokenArg, array $stateArg) use ($token): true {
                $this->assertSame(
                    $token,
                    $tokenArg,
                );

                $this->assertEmpty($stateArg);

                return true;
            })
            ->once();
    }

    public function test_it_returns_null_if_fetch_fails(): void
    {
        // Arrange

        $token = OAuthToken::accessToken('invalid-token');

        $baseProviderMock = $this->createBaseProviderStub();

        $baseProviderMock
            ->expects('performUserFetch')
            ->andThrow(new Exception('API Error'));

        // Act

        $actualUser = $baseProviderMock->fetchUser($token);

        // Assert

        $this->assertNull($actualUser);
    }

    public function test_it_returns_null_if_user_data_is_incomplete(): void
    {
        // Arrange

        $token = OAuthToken::accessToken('valid-token');

        $baseProviderMock = $this->createBaseProviderStub();

        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = '123';
        $socialiteUser->email = null; // <- Missing email

        $baseProviderMock->expects('performUserFetch')->andReturn($socialiteUser);

        // Act

        $actualUser = $baseProviderMock->fetchUser($token);

        // Assert

        $this->assertNull($actualUser);
    }

    public function test_it_returns_user_tokens(): void
    {
        // Arrange

        $baseProviderMock = $this->createBaseProviderStub();

        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = '123';
        $socialiteUser->email = fake()->safeEmail();
        $socialiteUser->token = $token = fake()->uuid();
        $socialiteUser->refreshToken = $refreshToken = fake()->uuid();

        // Act

        $actual = $baseProviderMock->getTokens($socialiteUser);

        // Assert

        $this->assertEquals(
            [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
            ],
            $actual,
        );
    }

    public function test_it_throws_not_supported_exception_for_revoke_token(): void
    {
        // Arrange

        $token = fake()->uuid();

        $baseProviderMock = $this->createBaseProviderStub();

        // Anticipate

        $this->expectExceptionObject(
            CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRevocation(ProviderEnum::Google->value)
        );

        // Act

        $baseProviderMock->revokeAccess($token);
    }

    public function test_it_throws_not_supported_exception_for_refresh_token(): void
    {
        // Arrange

        $token = fake()->uuid();

        $baseProviderMock = $this->createBaseProviderStub();

        // Anticipate

        $this->expectExceptionObject(
            CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRefresh(ProviderEnum::Google->value)
        );

        // Act

        $baseProviderMock->refreshToken($token);
    }

    /*
     * Mocks.
     */

    private function createBaseProviderStub(ProviderEnum $provider = ProviderEnum::Google): OAuthProviderBase&MockInterface
    {
        $socialiteMock = $this->mock(AbstractSocialiteProvider::class);

        /** @var OAuthProviderBase&MockInterface $baseProviderMock */
        $baseProviderMock = $this->partialMock(OAuthProviderBase::class, function (MockInterface $mock) use ($provider, $socialiteMock): void {
            $mock->expects('buildSocialite')->andReturn($socialiteMock);
            $mock->expects('getName')->atMost()->once()->andReturn($provider);
            $mock->expects('requestParameters')->atMost()->once()->andReturn(['extra' => 'param']);
        });

        $baseProviderMock->__construct();

        return $baseProviderMock;
    }
}
