<?php

declare(strict_types=1);

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\RegisterUserAction;
use App\Modules\Auth\DTOs\RegisterUserData;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(RegisterUserAction::class)]
class RegisterUserFunctionalTest extends FunctionalTestCase
{
    #[Test]
    public function test_it_registers_a_new_user_successfully(): void
    {
        // Arrange


        $data = new RegisterUserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secure-password',
        );

        $action = resolve(RegisterUserAction::class);

        // Act


        $user = $action->execute($data);

        // Assert


        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }
}
