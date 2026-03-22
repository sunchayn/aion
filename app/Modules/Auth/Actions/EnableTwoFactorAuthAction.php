<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\TwoFactorSetupPayload;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorAuthManager;
use App\Modules\Auth\Services\TwoFactorAuth\TwoFactorRecoveryCodeGeneratorService;
use App\Modules\Shared\Models\User;

/** @final */
class EnableTwoFactorAuthAction
{
    public function __construct(
        private readonly TwoFactorAuthManager $twoFactorAuthManager,
        private readonly TwoFactorRecoveryCodeGeneratorService $recoveryCodeGenerator,
    ) {}

    public function execute(User $user): TwoFactorSetupPayload
    {
        $secret = $this->twoFactorAuthManager->generateSecretKey();

        $recoveryCodes = $this->recoveryCodeGenerator->generate();

        $this->storeTwoFactorData($user, $secret, $recoveryCodes);

        return new TwoFactorSetupPayload(
            secret: $secret,
            qrCodeUrl: $this->twoFactorAuthManager->generateQrCode($user->email, $secret),
            recoveryCodes: $recoveryCodes,
        );
    }

    /**
     * @param  array<int, non-empty-string>  $recoveryCodes
     */
    private function storeTwoFactorData(User $user, string $secret, array $recoveryCodes): void
    {
        $attributes = (new UserTwoFactorAuth)
            // We are passing the attributes creation via the model's fill to trigger the right casts.
            // Note: We are using upsert which perform a direct SQL query without hydrating a model.
            ->fill([
                'user_id' => $user->id,
                'secret' => $secret,
                'recovery_codes' => $recoveryCodes,
                'confirmed_at' => null,
            ])
            ->getAttributes();

        UserTwoFactorAuth::query()->upsert(
            $attributes,
            uniqueBy: ['user_id'],
        );
    }
}
