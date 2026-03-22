<?php

declare(strict_types=1);

namespace Tests\App\Http\Web\Auth;

use App\Modules\Shared\Models\User;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LoginController::class)]
class LoginIntegrationTest extends FunctionalTestCase
{
    public function test_it_logs_in_successfully_with_valid_credentials(): void
    {
        // Arrange

        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Assert

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_it_fails_with_invalid_credentials(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Assert

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public static function loginDataProvider(): Generator
    {
        yield 'invalid email' => [
            'data' => ['email' => 'not-an-email', 'password' => 'password'],
        ];

        yield 'missing password' => [
            'data' => ['email' => 'user@example.com', 'password' => ''],
        ];
    }

    public function test_it_integrates(): void
    {
        // Arrange


        $user = User::factory()->create([
            'password' => $password = 'password',
        ]);

        // Act


        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        // Assert


        $response->assertRedirect(route('dashboard'));
    }
}
