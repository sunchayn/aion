<?php

namespace Tests\App\Modules\Auth\Models;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(MagicLink::class)]
#[Group('authentication')]
class MagicLinkFunctionalTest extends FunctionalTestCase
{
    public function test_scope_valid_returns_ready_to_use_links(): void
    {
        // Arrange

        $user = User::factory()->create();

        $validLink = MagicLink::factory()
            ->for($user)
            ->create([
                'expires_at' => now()->addMinute(),
                'used_at' => null,
            ]);

        MagicLink::factory()
            ->expired()
            ->for($user)
            ->create([
                'used_at' => null,
            ]);

        MagicLink::factory()
            ->used()
            ->for($user)
            ->create([
                'expires_at' => now()->addMinute(),
            ]);

        // Act

        $actual = MagicLink::query()->valid()->get();

        // Assert

        $this->assertCount(1, $actual);

        $this->assertTrue($actual->first()->is($validLink));
    }

    public function test_it_has_correct_casts(): void
    {
        // Arrange

        /** @var User $user */
        $user = User::factory()->create();

        $magicLink = MagicLink::factory()->for($user)->create([
            'expires_at' => now()->addHour(),
            'used_at' => now(),
            'created_at' => now()->subDay(),
        ]);

        // Act

        $actual = $magicLink->fresh();

        // Assert

        $this->assertInstanceOf(CarbonImmutable::class, $actual->expires_at);
        $this->assertInstanceOf(CarbonImmutable::class, $actual->used_at);
        $this->assertInstanceOf(CarbonImmutable::class, $actual->created_at);
    }

    public function test_it_has_user_relationship(): void
    {
        // Arrange

        /** @var User $user */
        $user = User::factory()->create();

        $magicLink = MagicLink::factory()->for($user)->create();

        // Act

        $actual = $magicLink->user;

        // Assert

        $this->assertInstanceOf(User::class, $actual);

        $this->assertTrue($actual->is($user));
    }
}
