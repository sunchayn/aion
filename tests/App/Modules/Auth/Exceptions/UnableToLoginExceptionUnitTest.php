<?php

namespace Tests\App\Modules\Auth\Exceptions;

use App\Modules\Auth\Exceptions\UnableToLoginException;
use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(UnableToLoginException::class)]
#[CoversClass(CannotPerformOauthOperationException::class)]
#[Group('authentication')]
#[Group('oauth')]
class UnableToLoginExceptionUnitTest extends UnitTestCase
{
    public function test_unable_to_login_exception_user_cannot_be_found_factory(): void
    {
        // Act

        $userNotFound = UnableToLoginException::becauseUserCannotBeFound();

        // Assert

        $this->assertEquals(UnableToLoginException::USER_CANNOT_BE_FOUND, $userNotFound->getCode());

        $this->assertEquals('Unable to log in with the provided credentials.', $userNotFound->getMessage());
    }

    public function test_unable_to_login_exception_invalid_credentials_factory(): void
    {
        // Act

        $invalidCredentials = UnableToLoginException::becauseOfInvalidCredentials();

        // Assert

        $this->assertEquals(UnableToLoginException::INVALID_CREDENTIALS, $invalidCredentials->getCode());

        $this->assertEquals('Unable to log in with the provided credentials.', $invalidCredentials->getMessage());
    }
}
