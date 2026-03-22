<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Fields\EventField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(EventField::class)]
#[Group('logging')]
class EventFieldUnitTest extends UnitTestCase
{
    public function test_it_formats_event_array_with_all_properties(): void
    {
        // Arrange

        $field = new EventField(
            action: 'login',
            category: 'authentication',
            outcome: 'success',
            reason: 'provided valid credentials',
            type: 'info',
            metadata: ['extra_key' => 'extra_value']
        );

        // Act

        $result = $field->toArray();

        // Assert

        $expected = [
            'event' => [
                'action' => 'login',
                'category' => ['authentication'],
                'outcome' => 'success',
                'reason' => 'provided valid credentials',
                'type' => ['info'],
                'extra_key' => 'extra_value',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_it_formats_event_array_with_minimum_properties(): void
    {
        // Arrange

        $field = new EventField(
            action: 'logout',
            category: 'authentication',
            outcome: 'success',
        );

        // Act

        $result = $field->toArray();

        // Assert

        $expected = [
            'event' => [
                'action' => 'logout',
                'category' => ['authentication'],
                'outcome' => 'success',
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
