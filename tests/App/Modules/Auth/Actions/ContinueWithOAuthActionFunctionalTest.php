<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\ContinueWithOAuthAction;
use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\RegisterUserAction;
use App\Modules\Auth\DataTransferObjects\ContinueWithOAuthData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Mockers\MocksOAuthProviders;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ContinueWithOAuthAction::class)]
#[CoversClass(ContinueWithOAuthData::class)]
#[Group('authentication')]
#[Group('oauth')]
class ContinueWithOAuthActionFunctionalTest extends FunctionalTestCase
{
    use MocksOAuthProviders;

    public function test_it_registers_new_oauth_user(): void
    {
        // Arrange

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-123',
            email: $oauthUserEmail = fake()->safeEmail(),
            name: $oauthUserName = fake()->name(),
            avatar: 'https://example.com/avatar.jpg',
            token: 'access-token-123',
            refreshToken: 'refresh-token-123',
            expiresIn: 3600,
        );

        $password = fake()->password();

        Str::createRandomStringsUsing(fn () => $password);

        $registeredUserStub = User::factory()->create();

        // Anticipate

        $registerUserActionMock = $this->mock(RegisterUserAction::class);
        $registerUserActionMock->expects('execute')->andReturn($registeredUserStub);

        $loginActionMock = $this->mock(LoginAction::class);
        $loginActionMock->shouldReceive('execute')->andReturn(
            new TokenLoginResponse(
                user: $registeredUserStub,
                authToken: fake()->uuid(),
            ),
        );

        // Act

        $actualLoggedInUser = resolve(ContinueWithOAuthAction::class)
            ->execute(
                new ContinueWithOAuthData(
                    provider: $provider = Arr::random(ProviderEnum::cases()),
                    user: null,
                    socialiteUser: $oauthUserMock,
                    alreadyLinked: false,
                ),
            );

        // Assert

        $this->assertInstanceOf(TokenLoginResponse::class, $actualLoggedInUser);
        $this->assertSame($registeredUserStub->id, $actualLoggedInUser->user->id);
        $this->assertNotEmpty($actualLoggedInUser->authToken);

        $this->assertDatabaseHas(
            'user_oauth_accounts',
            [
                'user_id' => $registeredUserStub->id,
                'provider' => $provider->value,
                'provider_user_id' => 'oauth-id-123',
                'provider_avatar' => 'https://example.com/avatar.jpg',
            ],
        );

        $oauthAccount = UserOAuthAccount::query()
            ->where('user_id', $registeredUserStub->id)
            ->first();

        $this->assertNotNull($oauthAccount->expires_at);
        $this->assertEquals('access-token-123', $oauthAccount->access_token);
        $this->assertEquals('refresh-token-123', $oauthAccount->refresh_token);

        $this->assertTrue($registeredUserStub->refresh()->hasVerifiedEmail());

        $registerUserActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(
                function (CreateUserData $dataArg) use ($oauthUserName, $oauthUserEmail, $password): bool {
                    $this->assertEquals($oauthUserName, $dataArg->name);
                    $this->assertEquals($oauthUserEmail, $dataArg->email);
                    $this->assertEquals($password, $dataArg->password);

                    return true;
                },
            )
            ->once();
    }

    /**
     * @param  array<string, mixed>|null  $oauthUserData
     */
    #[DataProvider('invalidOAuthDataProvider')]
    public function test_it_throws_exception_when_oauth_data_is_invalid(
        ?array $oauthUserData,
        ?User $user,
        bool $alreadyLinked,
    ): void {
        // Arrange

        $oauthUserMock = $oauthUserData !== null
            ? $this->mockSocialiteUser(...$oauthUserData)
            : null;

        // Anticipate

        $this->expectException(UnableToContinueWithOAuthException::class);

        // Act

        resolve(ContinueWithOAuthAction::class)
            ->execute(
                new ContinueWithOAuthData(
                    provider: ProviderEnum::Google,
                    user: $user,
                    socialiteUser: $oauthUserMock,
                    alreadyLinked: $alreadyLinked,
                ),
            );
    }

    public static function invalidOAuthDataProvider(): Generator
    {
        yield 'missing email' => [
            'oauthUserData' => [
                'id' => 'oauth-id-789',
                'email' => '',
                'name' => 'No Email User',
            ],
            'user' => null,
            'alreadyLinked' => false,
        ];

        yield 'null socialite user' => [
            'oauthUserData' => null,
            'user' => null,
            'alreadyLinked' => false,
        ];

        yield 'already linked but user is null' => [
            'oauthUserData' => [
                'id' => 'oauth-id-123',
                'email' => 'test@example.com',
                'name' => 'Existing User',
            ],
            'user' => null,
            'alreadyLinked' => true,
        ];
    }

    public function test_it_logs_in_existing_user_if_already_linked(): void
    {
        // Arrange

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-123',
            email: fake()->safeEmail(),
            name: fake()->name(),
        );

        $user = User::factory()->create();

        $loginActionMock = $this->mock(LoginAction::class);
        $loginActionMock->shouldReceive('execute')->once()->andReturn(
            $loggedInUserStub = new TokenLoginResponse(
                user: $user,
                authToken: fake()->uuid(),
            ),
        );

        // Act

        $actualLoggedInUser = resolve(ContinueWithOAuthAction::class)
            ->execute(
                new ContinueWithOAuthData(
                    provider: ProviderEnum::Google,
                    user: $user,
                    socialiteUser: $oauthUserMock,
                    alreadyLinked: true,
                ),
            );

        // Assert

        $this->assertInstanceOf(TokenLoginResponse::class, $actualLoggedInUser);

        $this->assertEquals($loggedInUserStub->getUser()->id, $actualLoggedInUser->getUser()->id);

        $this->assertEquals(
            $loggedInUserStub->toResponse(request())->getData(true)['data']['auth_token'],
            $actualLoggedInUser->toResponse(request())->getData(true)['data']['auth_token']
        );
    }

    public function test_it_links_and_logs_in_existing_user_if_not_already_linked(): void
    {
        // Arrange

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-123',
            email: $email = fake()->safeEmail(),
            name: fake()->name(),
        );

        $user = User::factory()->create(['email' => $email]);

        $loginActionMock = $this->mock(LoginAction::class);
        $loginActionMock->shouldReceive('execute')->once()->andReturn(
            new TokenLoginResponse(
                user: $user,
                authToken: fake()->uuid(),
            ),
        );

        // Act

        $actualLoggedInUser = resolve(ContinueWithOAuthAction::class)
            ->execute(
                new ContinueWithOAuthData(
                    provider: ProviderEnum::Google,
                    user: $user, // <- This is found by email in ContinueWithOAuthData::fromToken
                    socialiteUser: $oauthUserMock,
                    alreadyLinked: false, // <- But not yet linked in user_oauth_accounts
                ),
            );

        // Assert

        $this->assertDatabaseHas('user_oauth_accounts', [
            'user_id' => $user->id,
            'provider' => ProviderEnum::Google->value,
            'provider_user_id' => 'oauth-id-123',
        ]);

        $this->assertInstanceOf(TokenLoginResponse::class, $actualLoggedInUser);
        $this->assertEquals($user->id, $actualLoggedInUser->getUser()->id);
    }
}
