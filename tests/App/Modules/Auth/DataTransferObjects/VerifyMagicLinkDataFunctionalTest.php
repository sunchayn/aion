<?php

namespace Tests\App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\DataTransferObjects\VerifyMagicLinkData;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(VerifyMagicLinkData::class)]
#[Group('authentication')]
class VerifyMagicLinkDataFunctionalTest extends FunctionalTestCase
{
    public function test_it_creates_object_from_token(): void
    {
        // Arrange

        $user = User::factory()->create();

        $token = fake()->uuid();

        $magicLink = MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Act

        $data = VerifyMagicLinkData::fromToken($token, $user->email);

        // Assert

        $this->assertTrue($user->is($data->user));
        $this->assertNotNull($data->magicLink);
        $this->assertTrue($magicLink->is($data->magicLink));
    }

    public function test_it_creates_object_with_null_magic_link_if_not_found(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $data = VerifyMagicLinkData::fromToken('invalid-token', $user->email);

        // Assert

        $this->assertTrue($user->is($data->user));
        $this->assertNull($data->magicLink);
    }

    public function test_it_creates_object_with_new_user_if_email_not_found(): void
    {
        // Act

        $data = VerifyMagicLinkData::fromToken('invalid-token', 'notfound@example.com');

        // Assert

        $this->assertFalse($data->user->exists);
        $this->assertNull($data->magicLink);
    }
}
