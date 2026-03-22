<?php

namespace Database\Factories\Modules\Auth\Models;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<UserTwoFactorAuth>
 */
class UserTwoFactorAuthFactory extends Factory
{
    /** @var class-string<UserTwoFactorAuth> */
    protected $model = UserTwoFactorAuth::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'secret' => Crypt::encrypt($this->faker->uuid()),
            'recovery_codes' => array_map(
                fn () => $this->faker->lexify('?????-?????'),
                range(1, 8)
            ),
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): self
    {
        return $this->state(
            fn (array $attributes): array => [
                'confirmed_at' => now(),
            ],
        );
    }

    public function unconfirmed(): self
    {
        return $this->state(
            fn (array $attributes): array => [
                'confirmed_at' => null,
            ],
        );
    }
}
