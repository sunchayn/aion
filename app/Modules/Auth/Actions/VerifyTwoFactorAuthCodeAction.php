<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\VerifyTwoFactorAuthCodeData;
use App\Modules\Auth\Exceptions\TwoFactorAuthSecretIsUnsetException;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use Throwable;

/** @final */
class VerifyTwoFactorAuthCodeAction
{
    public function __construct(
        private readonly TwoFactorAuthManager $twoFactorAuthManager,
    ) {}

    /**
     * @throws TwoFactorAuthSecretIsUnsetException
     * @throws Throwable
     */
    public function execute(VerifyTwoFactorAuthCodeData $data): bool
    {
        throw_if(
            ! $data->twoFactorAuth instanceof UserTwoFactorAuth,
            TwoFactorAuthSecretIsUnsetException::class,
        );

        throw_if(
            blank($data->twoFactorAuth->secret),
            TwoFactorAuthSecretIsUnsetException::class,
        );

        if (! $this->twoFactorAuthManager->verify($data->twoFactorAuth->secret, $data->code)) {
            return false;
        }

        $data->twoFactorAuth->update([
            'confirmed_at' => now(),
        ]);

        return true;
    }
}
