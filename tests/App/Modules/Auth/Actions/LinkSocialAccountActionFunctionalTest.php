<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\LinkSocialAccountData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use App\Modules\Shared\Models\User;
use Closure;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Mockers\MocksOAuthProviders;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LinkSocialAccountAction::class)]
#[CoversClass(LinkSocialAccountData::class)]
#[Group('authentication')]
#[Group('oauth')]
class LinkSocialAccountActionFunctionalTest extends FunctionalTestCase
{
    use MocksOAuthProviders;

    #[DataProvider('linkingExistentUserDataProvider')]
    public function test_it_links_existing_user_to_oauth_provider(
        Closure $userSeeder,
        int $expectedOAuthAccountsCount,
    ): void {
        // Arrange

        /** @var User $existingUser */
        $existingUser = $userSeeder->call($this);

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-456',
            email: $existingUser->email,
            name: 'Updated Name',
            token: 'access-token-456',
        );

        $this->mockOAuthProviderToReturn($oauthUserMock);

        $token = OAuthToken::accessToken('access-token-456');

        // Act

        resolve(LinkSocialAccountAction::class)
            ->execute(
                LinkSocialAccountData::fromToken(
                    authenticatedUser: $existingUser,
                    provider: ProviderEnum::Google,
                    token: $token,
                ),
            );

        // Assert

        $this->assertDatabaseCount('user_oauth_accounts', $expectedOAuthAccountsCount);

        $this->assertDatabaseHas(
            'user_oauth_accounts',
            [
                'user_id' => $existingUser->id,
                'provider' => ProviderEnum::Google->value,
                'provider_user_id' => 'oauth-id-456',
            ],
        );

        $oauthAccount = UserOAuthAccount::query()
            ->where('user_id', $existingUser->id)
            ->where('provider', ProviderEnum::Google)
            ->first();

        $this->assertEquals('access-token-456', $oauthAccount->access_token);

        // Ensure user's attributes are unchanged.
        $this->assertEquals(
            $existingUser->attributesToArray(),
            $existingUser->refresh()->attributesToArray(),
        );
    }

    public static function linkingExistentUserDataProvider(): Generator
    {
        yield 'user is not linked to any oauth provider' => [
            'userSeeder' => fn () => User::factory()->create(),
            'expectedOAuthAccountsCount' => 1,
        ];

        yield 'user is not linked to any a different oauth provider' => [
            'userSeeder' => function () {
                $user = User::factory()->create();

                $oauthAccount = UserOAuthAccount::factory()->make();

                $oauthAccount->setRawAttributes(
                    ['provider' => '::completely_different_provider::']
                    + $oauthAccount->getAttributes(),
                );

                $oauthAccount->save();

                return $user;
            },
            'expectedOAuthAccountsCount' => 2,
        ];
    }

    public function test_it_throws_if_already_linked_to_different_account(): void
    {
        // Arrange

        $existingUser = User::factory()->create();
        $anotherUser = User::factory()->create();

        $oauthUserMock = $this->mockSocialiteUser(
            id: 'oauth-id-456',
            email: 'existing@example.com',
            name: 'Existing User',
        );

        UserOAuthAccount::factory()
            ->for($anotherUser) // <- Linked to a Different User
            ->create(['provider' => ProviderEnum::Google, 'provider_user_id' => 'oauth-id-456']);

        $this->mockOAuthProviderToReturn($oauthUserMock);

        $token = OAuthToken::accessToken('::token::');

        // Anticipate

        $this->expectException(UnableToContinueWithOAuthException::class);

        // Act

        resolve(LinkSocialAccountAction::class)
            ->execute(
                LinkSocialAccountData::fromToken(
                    authenticatedUser: $existingUser, // <- Current user
                    provider: ProviderEnum::Google,
                    token: $token,
                ),
            );
    }
}
