<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\SendMagicLinkController;
use App\Http\Api\Auth\Requests\SendMagicLinkRequest;
use App\Modules\Auth\Actions\SendMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\SendMagicLinkData;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(SendMagicLinkController::class)]
#[CoversClass(SendMagicLinkRequest::class)]
#[Group('authentication')]
class SendMagicLinkIntegrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_it_sends_magic_link_to_existing_user(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Anticipate

        $sendMagicLinkActionMock = $this->mock(
            SendMagicLinkAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn(null),
        );

        // Act

        $response = $this->postJson(
            route('api.v1.auth.magic-links.store'),
            [
                'email' => $user->email,
            ],
        );

        // Assert

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'message' => 'If an account exists, a magic link has been sent.',
            ],
        ]);

        $sendMagicLinkActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (SendMagicLinkData $data) use ($user): true {
                $this->assertTrue($data->user->is($user));

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $response = $this->postJson(
            route('api.v1.auth.magic-links.store'),
            [
                'email' => $user->email,
            ],
        );

        // Assert

        $response->assertOk();
    }
}
