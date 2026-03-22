<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Fields\ErrorField;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(ErrorField::class)]
#[Group('logging')]
class ErrorFieldUnitTest extends UnitTestCase
{
    public function test_it_formats_error_array(): void
    {
        // Arrange

        $field = new ErrorField(
            code: '500',
            id: 'ExceptionID',
            message: 'An error occurred',
            stackTrace: ['trace1', 'trace2'],
            type: 'RuntimeException'
        );

        // Act

        $result = $field->toArray();

        // Assert

        $expected = [
            'error' => [
                'code' => '500',
                'id' => 'ExceptionID',
                'message' => 'An error occurred',
                'stacktrace' => ['trace1', 'trace2'],
                'type' => 'RuntimeException',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_it_creates_from_throwable(): void
    {
        // Arrange

        $exception = new RuntimeException('Test exception', 400);

        $field = ErrorField::fromThrowable($exception);

        // Act

        $actual = $field->toArray();

        // Assert

        $this->assertEquals(['error'], array_keys($actual));

        $this->assertEquals(
            [
                'code' => '400',
                'id' => RuntimeException::class,
                'message' => 'Test exception',
                'type' => RuntimeException::class,
            ],
            Arr::except($actual['error'], ['stacktrace']),
        );

        $this->assertIsArray($actual['error']['stacktrace']);
    }
}
