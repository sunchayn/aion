<?php

namespace Database\Factories\Modules\Auth\Models;

use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserOAuthAccount>
 */
class UserOAuthAccountFactory extends Factory
{
    /** @var class-string<UserOAuthAccount> */
    protected $model = UserOAuthAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(ProviderEnum::cases()),
            'provider_user_id' => $this->faker->uuid(),
            'provider_avatar' => $this->faker->imageUrl(),
            'access_token' => $this->faker->uuid(),
            'refresh_token' => $this->faker->uuid(),
            'expires_at' => now()->addMonths(6),
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
}
