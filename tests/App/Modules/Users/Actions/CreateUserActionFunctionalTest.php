<?php

namespace Tests\App\Modules\Users\Actions;

use App\Modules\Shared\Models\User;
use App\Modules\Users\Actions\CreateUserAction;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(CreateUserAction::class)]
#[CoversClass(CreateUserData::class)]
#[Group('users')]
class CreateUserActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_creates_new_user(): void
    {
        // Arrange

        $data = new CreateUserData(
            name: fake()->name(),
            email: fake()->safeEmail(),
            password: fake()->password(),
        );

        // Act

        $user = resolve(CreateUserAction::class)->execute($data);

        // Assert

        $this->assertDatabaseHas(
            'users',
            [
                'name' => $data->name,
                'email' => $data->email,
            ],
        );

        $freshUser = User::query()->where('email', $data->email)->first();

        $this->assertTrue(Hash::check($data->password, $freshUser->password));

        $this->assertTrue($freshUser->is($user));
    }
}
