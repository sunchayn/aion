<?php

namespace Tests\App\Modules\Users\Models;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(User::class)]
#[Group('users')]
#[Group('models')]
class UserFunctionalTest extends FunctionalTestCase
{
    public function test_it_has_correct_casts(): void
    {
        // Arrange

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'secret',
        ]);

        // Act

        $actual = $user->fresh();

        // Assert

        $this->assertInstanceOf(CarbonImmutable::class, $actual->email_verified_at);

        $this->assertTrue(Hash::check('secret', $actual->password));
    }

    public function test_it_implements_jwt_subject_methods(): void
    {
        $this->skipTestWhenJwtIsNotAvailable();

        // Arrange

        $user = User::factory()->create();

        // Act & Assert

        $this->assertEquals($user->id, $user->getJWTIdentifier());

        $this->assertEquals([], $user->getJWTCustomClaims());
    }

    public function test_it_returns_name_and_email(): void
    {
        // Arrange

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Act & Assert

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_it_marks_email_as_verified(): void
    {
        // Arrange

        $user = User::factory()->unverified()->create();

        // Act

        $user->markEmailAsVerified();

        // Assert

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_it_has_two_factor_auth_relationship(): void
    {
        // Arrange

        $user = User::factory()->create();
        $twoFactor = UserTwoFactorAuth::factory()->create(['user_id' => $user->id]);

        // Act & Assert

        $this->assertTrue($user->twoFactorAuth->is($twoFactor));
    }
}
