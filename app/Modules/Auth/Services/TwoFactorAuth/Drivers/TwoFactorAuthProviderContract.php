<?php

namespace App\Modules\Auth\Services\TwoFactorAuth\Drivers;

interface TwoFactorAuthProviderContract
{
    public function generateSecretKey(): string;

    public function generateQrCode(string $holder, string $secretKey): string;

    public function verify(string $secret, string $code): bool;
}
