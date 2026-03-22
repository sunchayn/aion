<?php

namespace Tests\App\Modules\Auth\Exceptions;

use App\Modules\Auth\Exceptions\InvalidEmailVerificationLinkException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(InvalidEmailVerificationLinkException::class)]
#[Group('authentication')]
class InvalidEmailVerificationLinkExceptionUnitTest extends FunctionalTestCase
{
    public function test_because_of_invalid_hash_creates_exception_with_correct_message(): void
    {
        // Act

        $exception = InvalidEmailVerificationLinkException::becauseOfInvalidHash();

        // Assert

        $this->assertEquals('Invalid verification link.', $exception->getMessage());
    }

    public function test_render_returns_json_response_with_message_and_403_status(): void
    {
        // Arrange

        $exception = InvalidEmailVerificationLinkException::becauseOfInvalidHash();

        // Act

        $response = $exception->render();

        // Assert

        $this->assertEquals(403, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertArrayHasKey('message', $data);

        $this->assertEquals('Invalid verification link.', $data['message']);
    }
}
