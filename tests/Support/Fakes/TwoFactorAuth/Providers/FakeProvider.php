<?php

namespace Tests\Support\Fakes\TwoFactorAuth\Providers;

use App\Modules\Auth\Services\TwoFactorAuth\Drivers\TwoFactorAuthProviderContract;
use Illuminate\Support\Str;

class FakeProvider implements TwoFactorAuthProviderContract
{
    private bool $shouldPassVerification = true;

    public function shouldPassVerification(bool $pass = true): self
    {
        $this->shouldPassVerification = $pass;

        return $this;
    }

    public function generateSecretKey(): string
    {
        return Str::random(32);
    }

    public function generateQrCode(string $holder, string $secretKey): string
    {
        return fake()->url();
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->shouldPassVerification;
    }
}
