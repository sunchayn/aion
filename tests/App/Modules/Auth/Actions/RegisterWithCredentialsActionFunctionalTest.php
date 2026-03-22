<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\RegisterUserAction;
use App\Modules\Auth\Actions\RegisterWithCredentialsAction;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\RegisterWithCredentialsData;
use App\Modules\Auth\Responses\TokenLoginResponse;
use App\Modules\Shared\Models\User;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegisterWithCredentialsAction::class)]
#[CoversClass(RegisterWithCredentialsData::class)]
#[Group('authentication')]
class RegisterWithCredentialsActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_registers_and_logs_in_user(): void
    {
        // Arrange

        $registerWithCredentialsData = new RegisterWithCredentialsData(
            name: $name = fake()->name(),
            email: $email = fake()->safeEmail(),
            password: $password = fake()->password(),
        );

        $dummyLoggedInUser = new TokenLoginResponse(
            user: new User(['id' => fake()->randomNumber()]),
            authToken: fake()->uuid(),
        );

        $loginActionMock = $this->mock(LoginAction::class);
        $loginActionMock->expects('execute')->andReturn($dummyLoggedInUser);

        $registerUserActionMock = $this->mock(RegisterUserAction::class);

        $registerUserActionMock
            ->expects('execute')
            ->andReturn(
                $newUser = User::factory()->create(['email' => $email])
            );

        // Act

        $loggedInUser = resolve(RegisterWithCredentialsAction::class)->execute($registerWithCredentialsData);

        // Assert

        $this->assertSame($dummyLoggedInUser, $loggedInUser);

        $loginActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (LoginData $dataArg) use ($newUser): true {
                $this->assertTrue(
                    $newUser->is($dataArg->user)
                );

                return true;
            })
            ->once();

        $registerUserActionMock
            ->shouldHaveReceived('execute')
            ->withArgs(function (CreateUserData $dataArg) use ($name, $email, $password): true {
                $this->assertEquals($name, $dataArg->name);
                $this->assertEquals($email, $dataArg->email);
                $this->assertEquals($password, $dataArg->password);

                return true;
            })
            ->once();
    }
}
