<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Fields\UserField;
use App\Modules\Shared\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(UserField::class)]
#[Group('logging')]
class UserFieldUnitTest extends UnitTestCase
{
    public function test_it_formats_empty_array_when_no_user_provided(): void
    {
        // Arrange

        $field = new UserField(null);

        // Act

        $actual = $field->toArray();

        // Assert

        $this->assertEquals(
            ['user' => []],
            $actual,
        );
    }

    public function test_it_formats_user_array_with_id(): void
    {
        // Arrange

        $user = new User()->forceFill([
            'id' => 42,
            'email' => 'noop@email.com',
        ]);

        $field = new UserField($user);

        // Act

        $actual = $field->toArray();

        // Assert

        $this->assertEquals(
            [
                'user' => ['id' => 42],
            ],
            $actual,
        );
    }
}
