<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Fields\ContextField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(ContextField::class)]
#[Group('logging')]
class ContextFieldUnitTest extends UnitTestCase
{
    public function test_it_formats_context_array(): void
    {
        // Arrange

        $data = ['user_id' => 123, 'ip' => '127.0.0.1'];

        $field = new ContextField($data);

        // Act

        $result = $field->toArray();

        // Assert

        $this->assertEquals(['context' => $data], $result);
    }
}
