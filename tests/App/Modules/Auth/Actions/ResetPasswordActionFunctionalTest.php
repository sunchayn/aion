<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\ResetPasswordAction;
use App\Modules\Auth\DataTransferObjects\ResetPasswordData;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(ResetPasswordAction::class)]
#[CoversClass(ResetPasswordData::class)]
#[Group('authentication')]
class ResetPasswordActionFunctionalTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    public function test_it_resets_password_using_broker(): void
    {
        // Arrange

        $user = User::factory()
            ->create([
                'password' => 'old-password',
            ]);

        $token = Password::broker()->createToken($user);

        $data = new ResetPasswordData(
            email: $user->email,
            password: $newPassword = 'new-password123',
            token: $token,
        );

        // Act

        $actual = resolve(ResetPasswordAction::class)->execute($data);

        // Assert

        $this->assertTrue($actual);

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                $newPassword,
                $user->password,
            ),
        );

        Event::assertDispatchedOnce(PasswordReset::class);

        Event::assertDispatched(
            PasswordReset::class,
            function (PasswordReset $event) use ($user): true {
                $this->assertTrue(
                    $event->user->is($user),
                );

                return true;
            },
        );
    }

    public function test_it_returns_false_on_invalid_token(): void
    {
        // Arrange

        $user = User::factory()
            ->create([
                'password' => $oldPassword = 'old-password',
            ]);

        $data = new ResetPasswordData(
            email: $user->email,
            password: 'new-password123',
            token: fake()->uuid(),
        );

        // Act

        $actual = resolve(ResetPasswordAction::class)->execute($data);

        // Assert

        $this->assertFalse($actual);

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                $oldPassword,
                $user->password,
            ),
        );

        Event::assertNotDispatched(PasswordReset::class);
    }
}
