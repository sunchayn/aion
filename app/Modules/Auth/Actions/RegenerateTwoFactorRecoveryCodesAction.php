<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorRecoveryCodeGeneratorService;
use App\Modules\Shared\Models\User;

/** @final */
class RegenerateTwoFactorRecoveryCodesAction
{
    public function __construct(
        private readonly TwoFactorRecoveryCodeGeneratorService $recoveryCodeGenerator,
    ) {}

    /**
     * @return array<array-key, string>|null
     */
    public function execute(User $user): ?array
    {
        if (! $user->twoFactorAuth instanceof UserTwoFactorAuth) {
            return null;
        }

        if ($user->twoFactorAuth->confirmed_at === null) {
            return null;
        }

        $recoveryCodes = $this->recoveryCodeGenerator->generate();

        $user->twoFactorAuth->update([
            'recovery_codes' => $recoveryCodes,
        ]);

        return $recoveryCodes;
    }
}
