<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\RegisterUserAction;
use App\Modules\Shared\Models\User;
use App\Modules\Users\Actions\CreateUserAction;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegisterUserAction::class)]
#[Group('authentication')]
class RegisterUserActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_creates_user_and_dispatches_registered_event(): void
    {
        // Arrange

        Event::fake();

        $data = new CreateUserData(
            name: fake()->name(),
            email: fake()->safeEmail(),
            password: fake()->password(),
        );

        $userStub = User::factory()->make(['email' => $data->email]);

        $createUserActionMock = $this->mock(CreateUserAction::class);
        $createUserActionMock
            ->expects('execute')
            ->withArgs(fn (CreateUserData $arg): bool => $arg->name === $data->name
                && $arg->email === $data->email
                && $arg->password === $data->password)
            ->andReturn($userStub);

        // Act

        $actualUser = resolve(RegisterUserAction::class)->execute($data);

        // Assert

        $this->assertSame($userStub, $actualUser);

        Event::assertDispatched(
            Registered::class,
            fn (Registered $event): bool => $event->user->is($userStub),
        );

        Event::assertDispatchedOnce(Registered::class);
    }
}
