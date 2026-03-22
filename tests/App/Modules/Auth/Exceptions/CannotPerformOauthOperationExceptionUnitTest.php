<?php

namespace Tests\App\Modules\Auth\Exceptions;

use App\Modules\Auth\OAuthProviders\Exceptions\CannotPerformOauthOperationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(CannotPerformOauthOperationException::class)]
#[Group('authentication')]
class CannotPerformOauthOperationExceptionUnitTest extends UnitTestCase
{
    public function test_factories(): void
    {
        $this->assertEquals(
            'Unsupported feature for this oauth provider [Google]. Feature requested: <Refresh Auth token>',
            CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRefresh('Google')->getMessage()
        );

        $this->assertEquals(
            'Unsupported feature for this oauth provider [Apple]. Feature requested: <Revoke Auth token>',
            CannotPerformOauthOperationException::becauseProviderDoesntSupportTokenRevocation('Apple')->getMessage()
        );

        $this->assertEquals(
            'Unable to find the name from [Github]\'s remote user.',
            CannotPerformOauthOperationException::becauseUnableToFindNameInRemoteUser('Github')->getMessage()
        );

        $this->assertEquals(
            'Feature <Something> is not implemented for [Apple].',
            CannotPerformOauthOperationException::becauseNotImplemented('Apple', 'Something')->getMessage()
        );

    }
}
