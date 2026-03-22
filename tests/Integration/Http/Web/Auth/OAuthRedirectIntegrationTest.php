<?php

namespace Tests\Integration\Http\Web\Auth;

use App\Http\Web\Auth\Controllers\OAuthRedirectController;
use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\Providers\AppleOAuthProvider;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(OAuthRedirectController::class)]
#[Group('authentication')]
class OAuthRedirectIntegrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipTestWhenSocialiteIsNotAvailable();
    }

    #[DataProvider('operationTypeProvider')]
    public function test_it_redirects_to_valid_provider(string $operationType): void
    {
        // Arrange

        $providerMock = Mockery::mock(AppleOAuthProvider::class);
        $providerMock->expects('redirectToProvider')->once()->andReturn(redirect('https://apple.com/oauth'));

        $factoryMock = Mockery::mock(ProvidersFactory::class);
        $factoryMock->expects('make')->with(ProviderEnum::Apple)->once()->andReturn($providerMock);

        $this->instance(ProvidersFactory::class, $factoryMock);

        // Act

        $response = $this->get(
            route('auth.oauth-redirect', ['provider' => 'apple', 'operation_type' => $operationType]),
        );

        // Assert

        $response->assertRedirect('https://apple.com/oauth');
    }

    public static function operationTypeProvider(): Generator
    {
        yield 'login operation' => [
            'operationType' => OperationTypeEnum::Auth->value,
        ];

        yield 'link operation' => [
            'operationType' => OperationTypeEnum::Link->value,
        ];
    }

    public function test_it_aborts_on_invalid_provider(): void
    {

        $response = $this->get(
            route('auth.oauth-redirect', ['provider' => 'invalid', 'operation_type' => 'auth']),
        );

        $response->assertNotFound();
    }

    public function test_it_integrates(): void
    {
        // Arrange

        config([
            'services.google.oauth.redirect_url' => fake()->url(),
            'services.google.oauth.client_id' => fake()->uuid(),
            'services.google.oauth.client_secret' => fake()->uuid(),
        ]);

        // Act

        $response = $this->get(
            route('auth.oauth-redirect', ['provider' => 'google', 'operation_type' => 'auth']),
        );

        // Assert

        $response->assertRedirect();
    }
}
