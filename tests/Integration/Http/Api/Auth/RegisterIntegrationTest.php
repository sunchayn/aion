<?php

namespace Tests\Integration\Http\Api\Auth;

use App\Http\Api\Auth\Controllers\RegisterWithCredentialsController;
use App\Http\Api\Auth\Requests\RegisterWithCredentialsRequest;
use App\Modules\Auth\Actions\RegisterWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\RegisterWithCredentialsData;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegisterWithCredentialsController::class)]
#[CoversClass(RegisterWithCredentialsRequest::class)]

class RegisterIntegrationTest extends FunctionalTestCase
{
    public function test_it_registers_user_with_valid_data(): void
    {
        // Arrange

        $name = 'John Doe';
        $email = 'john@example.com';
        $password = 'password123';

        $dummyLoggedInUser = new TokenLoginResponse(
            user: new User(['id' => 1]),
            authToken: 'token',
            refreshToken: 'refresh-token',
        );

        // Anticipate

        $mock = $this->mock(
            RegisterWithCredentialsAction::class,
            fn (MockInterface $mock) => $mock->expects('execute')
                ->withAnyArgs()
                ->once()
                ->andReturn($dummyLoggedInUser),
        );

        // Act

        $response = $this->postJson(route('api.v1.auth.users.store'), [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        // Assert

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'user_id' => $dummyLoggedInUser->getUser()->id,
                'auth_token' => 'token',
                'refresh_token' => 'refresh-token',
            ],
        ]);

        $mock->shouldHaveReceived('execute')
            ->withArgs(function (RegisterWithCredentialsData $data) use ($name, $email, $password): true {
                $this->assertSame($name, $data->name);
                $this->assertSame($email, $data->email);
                $this->assertSame($password, $data->password);

                return true;
            });
    }

    public function test_it_integrates(): void
    {
        // Arrange

        // Act

        $response = $this->postJson(route('api.v1.auth.users.store'), [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        // Assert

        $response->assertStatus(422);
    }
}
