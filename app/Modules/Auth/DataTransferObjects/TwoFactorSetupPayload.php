<?php

namespace App\Modules\Auth\DataTransferObjects;

final readonly class TwoFactorSetupPayload
{
    /**
     * @param  array<string>  $recoveryCodes
     */
    public function __construct(
        public string $secret,
        public string $qrCodeUrl,
        public array $recoveryCodes,
    ) {}
}
