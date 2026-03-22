<?php

namespace Database\Factories\Modules\Auth\Models;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<MagicLink>
 */
class MagicLinkFactory extends Factory
{
    /** @var class-string<MagicLink> */
    protected $model = MagicLink::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Hash::make($this->faker->uuid()),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
            'created_at' => now(),
        ];
    }

    public function expired(): self
    {
        return $this->state(
            fn (array $attributes): array => [
                'expires_at' => now()->subMinutes(5),
            ],
        );
    }

    public function used(): self
    {
        return $this->state(
            fn (array $attributes): array => [
                'used_at' => now()->subMinutes(5),
            ],
        );
    }
}
