<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\VerifyMagicLinkController;
use App\Http\Api\Auth\Requests\VerifyMagicLinkRequest;
use App\Modules\Auth\Actions\VerifyMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\VerifyMagicLinkData;
use App\Modules\Auth\Exceptions\UnusableMagicLinkException;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Responses\TokenLoginResponse;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(VerifyMagicLinkController::class)]
#[CoversClass(VerifyMagicLinkRequest::class)]
#[Group('authentication')]
class VerifyMagicLinkIntegrationTest extends FunctionalTestCase
{
    public function test_it_verifies_magic_link_and_returns_user_tokens(): void
    {
        // Arrange

        $plainToken = fake()->uuid();
        $magicLink = MagicLink::factory()->state(['token' => Hash::make($plainToken)])->create();
        $user = $magicLink->user;

        $loggedInUser = new TokenLoginResponse(
            user: $user,
            authToken: 'test-auth-token',
            refreshToken: 'test-refresh-token',
        );

        // Anticipate

        $verifyMagicLinkActionMock = $this->mock(
            VerifyMagicLinkAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andReturn($loggedInUser),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.magic-links.verifications.store'),
            [
                'token' => $plainToken,
                'email' => $user->email,
            ],
        );

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'user_id' => $user->id,
                'auth_token' => 'test-auth-token',
                'refresh_token' => 'test-refresh-token',
                'two_factor' => false,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => true,
                'avatar_url' => null,
                'oauth_providers' => [],
            ],
        ]);

        $verifyMagicLinkActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (VerifyMagicLinkData $dataArg) use ($magicLink): true {
                $this->assertTrue(
                    $magicLink->is($dataArg->magicLink),
                );

                return true;
            });
    }

    public function test_it_handles_verification_errors(): void
    {
        // Anticipate

        $this->mock(
            VerifyMagicLinkAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->andThrow(new UnusableMagicLinkException),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.magic-links.verifications.store'),
            [
                'token' => fake()->uuid(),
                'email' => fake()->safeEmail(),
            ],
        );

        // Assert

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(
            [
                'token' => 'The provided magic link is invalid or has expired.',
            ],
        );
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $plainToken = fake()->uuid();
        $magicLink = MagicLink::factory()->state(['token' => Hash::make($plainToken)])->create();

        // Act

        $response = $this->postJson(
            route('api.v1.auth.magic-links.verifications.store'),
            [
                'token' => $plainToken,
                'email' => $magicLink->user->email,
            ],
        );

        // Assert

        $response->assertOk();
    }
}
