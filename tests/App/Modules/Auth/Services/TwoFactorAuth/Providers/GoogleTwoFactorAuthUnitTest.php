<?php

namespace Tests\App\Modules\Auth\Services\TwoFactorAuth\Providers;

use App\Modules\Auth\Services\TwoFactorAuth\Drivers\GoogleTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\Exceptions\UnableToGenerateSecretKeyException;
use Exception;
use Illuminate\Config\Repository;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\TestCases\UnitTestCase;

#[CoversClass(GoogleTwoFactorAuth::class)]
#[Group('authentication')]
#[Group('2fa')]
class GoogleTwoFactorAuthUnitTest extends UnitTestCase
{
    public function test_it_generates_secret_key(): void
    {
        // Arrange

        $google2faMock = Mockery::mock(Google2FA::class);
        $configMock = Mockery::mock(Repository::class);

        $google2faMock->expects('generateSecretKey')->andReturn($secret = fake()->uuid());

        $googleTwoFactorAuth = new GoogleTwoFactorAuth($google2faMock, $configMock);

        // Act

        $actual = $googleTwoFactorAuth->generateSecretKey();

        // Assert

        $this->assertEquals($secret, $actual);
    }

    public function test_it_throws_exception_if_secret_generation_fails(): void
    {
        // Arrange

        $google2faMock = Mockery::mock(Google2FA::class);
        $configMock = Mockery::mock(Repository::class);

        $googleTwoFactorAuth = new GoogleTwoFactorAuth($google2faMock, $configMock);

        // Anticipate

        $google2faMock->expects('generateSecretKey')->andThrow(new Exception('Error'));

        $this->expectException(UnableToGenerateSecretKeyException::class);

        $this->expectExceptionMessage('Unable to generate 2FA secret key');

        // Act

        $googleTwoFactorAuth->generateSecretKey();
    }

    public function test_it_generates_qr_code(): void
    {
        // Arrange

        $google2faMock = Mockery::mock(Google2FA::class);
        $configMock = Mockery::mock(Repository::class);

        $holderEmail = fake()->safeEmail();
        $appName = fake()->word();
        $secretKey = fake()->uuid();

        $googleTwoFactorAuth = new GoogleTwoFactorAuth($google2faMock, $configMock);

        // Anticipate

        $configMock->expects('get')->with('app.name')->andReturn($appName);

        $google2faMock
            ->expects('getQRCodeUrl')
            ->withAnyArgs()
            ->andReturn($url = 'https://qr-code-url');

        // Act

        $actual = $googleTwoFactorAuth->generateQrCode($holderEmail, $secretKey);

        // Assert

        $this->assertEquals($url, $actual);

        $google2faMock
            ->shouldHaveReceived('getQRCodeUrl')
            ->withArgs(function (string $appNameArg, string $holderEmailArg, string $secretKeyArg) use ($appName, $holderEmail, $secretKey): true {
                $this->assertEquals(
                    $appName,
                    $appNameArg,
                );

                $this->assertEquals(
                    $holderEmail,
                    $holderEmailArg,
                );

                $this->assertEquals(
                    $secretKey,
                    $secretKeyArg,
                );

                return true;
            });
    }

    public function test_it_verifies_code(): void
    {
        // Arrange

        $google2faMock = Mockery::mock(Google2FA::class);
        $configMock = Mockery::mock(Repository::class);

        $googleTwoFactorAuth = new GoogleTwoFactorAuth($google2faMock, $configMock);

        // Anticipate

        $google2faMock->expects('verifyKey')->with('secret', '123456')->andReturnTrue();

        $google2faMock->expects('verifyKey')->with('secret', 'wrong')->andReturnFalse();

        // Act & Assert

        $this->assertTrue($googleTwoFactorAuth->verify('secret', '123456'));

        $this->assertFalse($googleTwoFactorAuth->verify('secret', 'wrong'));
    }
}
