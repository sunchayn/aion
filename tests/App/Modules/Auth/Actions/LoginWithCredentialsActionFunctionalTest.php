<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\LoginWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\LoginWithCredentialsData;
use App\Modules\Auth\Exceptions\UnableToLoginException;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LoginWithCredentialsAction::class)]
#[CoversClass(LoginWithCredentialsData::class)]
#[Group('authentication')]
class LoginWithCredentialsActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_logs_in_with_valid_credentials(): void
    {
        // Arrange

        $user = User::factory()->create([
            'email' => $email = fake()->safeEmail(),
            'password' => $password = fake()->password(),
        ]);

        $dummyLoggedInUser = new TokenLoginResponse(user: $user, authToken: 'fake-token');

        $loginActionMock = $this->mock(LoginAction::class);
        $loginActionMock->expects('execute')->andReturn($dummyLoggedInUser);

        // Act

        $loggedInUser = resolve(LoginWithCredentialsAction::class)
            ->execute(
                LoginWithCredentialsData::fromEmail(
                    $email,
                    $password,
                ),
            );

        // Assert

        $this->assertEquals($user->id, $loggedInUser->getUser()->id);

        $this->assertInstanceOf(TokenLoginResponse::class, $loggedInUser);

        $loginActionMock->shouldHaveReceived('execute')
            ->withArgs(function (LoginData $dataArg) use ($user): true {
                $this->assertTrue(
                    $dataArg->user->is($user),
                );

                return true;
            });
    }

    public function test_it_throws_exception_for_invalid_password(): void
    {
        // Arrange

        User::factory()->create([
            'email' => $email = fake()->safeEmail(),
            'password' => fake()->password(),
        ]);

        $data = LoginWithCredentialsData::fromEmail($email, 'wrong-password');

        // Assert

        $this->expectExceptionObject(UnableToLoginException::becauseOfInvalidCredentials());

        // Act

        resolve(LoginWithCredentialsAction::class)->execute($data);
    }

    public function test_it_throws_exception_if_user_not_found(): void
    {
        // Arrange

        $data = LoginWithCredentialsData::fromEmail('nonexistent@example.com', 'password123');

        // Assert

        $this->expectExceptionObject(UnableToLoginException::becauseUserCannotBeFound());

        // Act

        resolve(LoginWithCredentialsAction::class)->execute($data);
    }
}
