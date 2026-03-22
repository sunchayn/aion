<?php

namespace Tests\App\Http\Api\Shared\Resources;

use App\Http\Api\Shared\Resources\MessageResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessageResource::class)]
#[Group('resources')]
class MessageResourceUnitTest extends TestCase
{
    public function test_it_transforms_to_array(): void
    {
        // Arrange

        $message = 'Test message';

        $resource = new MessageResource($message);

        $request = Request::create('/ping');

        // Act

        $actual = $resource->toArray($request);

        // Assert

        $this->assertSame(['message' => $message], $actual);
    }
}
