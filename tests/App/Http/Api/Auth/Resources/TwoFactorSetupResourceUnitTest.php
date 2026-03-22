<?php

namespace Tests\App\Http\Api\Auth\Resources;

use App\Http\Api\Auth\Resources\TwoFactorSetupResource;
use App\Modules\Auth\DataTransferObjects\TwoFactorSetupPayload;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(TwoFactorSetupResource::class)]
#[Group('authentication')]
class TwoFactorSetupResourceUnitTest extends UnitTestCase
{
    public function test_it_returns_correct_array_structure(): void
    {
        // Arrange

        $payload = new TwoFactorSetupPayload(
            secret: 'secret-key',
            qrCodeUrl: 'https://example.com/qr',
            recoveryCodes: ['code1', 'code2'],
        );

        $resource = new TwoFactorSetupResource($payload);
        $request = resolve(Request::class);

        // Act

        $result = $resource->toArray($request);

        // Assert

        $this->assertEquals([
            'secret' => $payload->secret,
            'qr_code_url' => $payload->qrCodeUrl,
            'recovery_codes' => $payload->recoveryCodes,
        ], $result);
    }
}
