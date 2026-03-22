<?php

namespace Tests\App\Modules\Auth\Services\TwoFactorAuth;

use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorRecoveryCodeGeneratorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwoFactorRecoveryCodeGeneratorService::class)]
class TwoFactorRecoveryCodeGeneratorServiceUnitTest extends TestCase
{
    public function test_it_generates_eight_recovery_codes_in_correct_format(): void
    {
        // Arrange

        $service = new TwoFactorRecoveryCodeGeneratorService;

        // Act

        $codes = $service->generate();

        // Assert

        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);

        foreach ($codes as $code) {
            $this->assertIsString($code);
            // Verify format like "abcde-12345"
            $this->assertMatchesRegularExpression('/^[a-z0-9]{5}-[a-z0-9]{5}$/', $code);
        }
    }
}
