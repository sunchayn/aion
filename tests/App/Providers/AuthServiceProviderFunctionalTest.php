<?php

declare(strict_types=1);

namespace Tests\App\Providers;

use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Shared\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Providers\Stubs\DummyTokenIssuer;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(AuthServiceProvider::class)]
#[Group('providers')]
class AuthServiceProviderFunctionalTest extends FunctionalTestCase
{
    public function test_it_binds_auth_token_issuer_contract_based_on_config(): void
    {
        // Arrange

        config(['auth.tokens.issuer' => DummyTokenIssuer::class]);

        new AuthServiceProvider($this->app)->register();

        // Act

        $actual = $this->app->make(AuthTokenIssuerContract::class);

        // Assert

        $this->assertInstanceOf(
            DummyTokenIssuer::class,
            $actual,
        );
    }

    public function test_it_boots_email_verification_defaults(): void
    {
        // Arrange

        $frontendNoticeUrl = 'https://frontend.com/verify-email';
        Config::set('webhooks.frontend.redirects.email_verification_notice', $frontendNoticeUrl);

        $user = User::factory()->unverified()->create();

        // Act

        $notification = new VerifyEmail;
        $url = $notification->toMail($user)->actionUrl;

        // Assert

        $this->assertStringStartsWith($frontendNoticeUrl.'?', $url);

        $this->assertEmailVerificationUrlIsCorrect($user, $url);
    }

    /*
     * Asserts.
     */

    public function assertEmailVerificationUrlIsCorrect(User $user, string $url): void
    {
        $this->assertStringContainsString('id='.$user->getKey(), $url);
        $this->assertStringContainsString('hash='.sha1($user->getEmailForVerification()), $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
    }
}
