<?php

namespace App\Modules\Auth\Services\TwoFactorAuth\Drivers;

use App\Modules\Auth\Services\TwoFactorAuth\Exceptions\UnableToGenerateSecretKeyException;
use Illuminate\Config\Repository;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

final readonly class GoogleTwoFactorAuth implements TwoFactorAuthProviderContract
{
    public function __construct(
        private Google2FA $google2fa,
        private Repository $config,
    ) {}

    /**
     * @throws UnableToGenerateSecretKeyException
     */
    public function generateSecretKey(): string
    {
        try {
            return $this->google2fa->generateSecretKey();
        } catch (Throwable $googleTwoFactorException) {
            throw new UnableToGenerateSecretKeyException(
                message: 'Unable to generate 2FA secret key',
                previous: $googleTwoFactorException,
            );
        }
    }

    public function generateQrCode(string $holder, string $secretKey): string
    {
        return $this->google2fa->getQRCodeUrl(
            company: $this->config->get('app.name'),
            holder: $holder,
            secret: $secretKey,
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }
}
