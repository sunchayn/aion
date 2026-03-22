<?php

namespace Tests\App\Modules\Auth\Actions;

use App\Modules\Auth\Actions\LogoutAction;
use Illuminate\Auth\AuthManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(LogoutAction::class)]
#[Group('authentication')]
class LogoutActionFunctionalTest extends FunctionalTestCase
{
    public function test_it_logs_out(): void
    {
        // Arrange

        $authManagerMock = $this->mock(AuthManager::class);

        // Anticipate

        $authManagerMock->shouldReceive('logout')->once();

        $logoutAction = resolve(LogoutAction::class);

        // Act

        $logoutAction->execute();
    }
}
