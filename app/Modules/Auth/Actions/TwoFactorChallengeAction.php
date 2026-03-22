<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\TwoFactorChallengeData;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;

/** @final */
class TwoFactorChallengeAction
{
    public function __construct(
        private readonly TwoFactorAuthManager $twoFactorAuthManager,
    ) {}

    public function execute(TwoFactorChallengeData $data): bool
    {
        if (! $data->twoFactorAuth instanceof UserTwoFactorAuth) {
            return true;
        }

        // If the user started 2FA creation process but didn't confirm it,
        // we skip the challenge verification process.
        if ($data->twoFactorAuth->confirmed_at === null) {
            return true;
        }

        if ($data->recoveryCode) {
            return $this->verifyUsingRecoveryCode($data);
        }

        // $data->code is not null at this point @phpstan-ignore argument.type
        return $this->twoFactorAuthManager->verify($data->twoFactorAuth->secret, $data->code);
    }

    public function verifyUsingRecoveryCode(TwoFactorChallengeData $data): bool
    {
        $recoveryCodes = $data->twoFactorAuth->recovery_codes ?? [];

        if (in_array($data->recoveryCode, $recoveryCodes, true)) {
            // @phpstan-ignore-next-line `$data->twoFactorAuth` is not null at this point.
            $data->twoFactorAuth->update([
                'recovery_codes' => array_values(
                    // Remove the used code from the list of recovery codes.
                    array_diff($recoveryCodes, [$data->recoveryCode])
                ),
            ]);

            return true;
        }

        return false;
    }
}
