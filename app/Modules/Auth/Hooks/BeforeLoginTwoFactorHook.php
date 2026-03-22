<?php

namespace App\Modules\Auth\Hooks;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Responses\LoginResponse;
use App\Modules\Auth\Responses\SessionLoginResponse;
use App\Modules\Auth\Responses\TokenLoginResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Crypt;

/**
 * A beforeLogin callback that interrupts authentication when the user has 2FA enabled.
 * Instead of issuing an auth token, it issues a short-lived encrypted two-factor token
 * that the client must exchange at the 2FA challenge endpoint.
 */
class BeforeLoginTwoFactorHook
{
    public const int TWO_FACTOR_TOKEN_TTL_MINUTES = 15;

    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    public function __invoke(LoginData $data): ?LoginResponse
    {
        if ($data->skipTwoFactor || $data->user->twoFactorAuth?->confirmed_at === null) {
            return null;
        }

        $twoFactorToken = Crypt::encryptString((string) json_encode([
            'id' => $data->user->getAuthIdentifier(),
            'expires_at' => now()->addMinutes(self::TWO_FACTOR_TOKEN_TTL_MINUTES)->timestamp,
        ]));

        if ($this->authManager->guard($data->guard) instanceof StatefulGuard) {
            return SessionLoginResponse::pendingTwoFactorAuth(
                user: $data->user,
                twoFactorToken: $twoFactorToken,
            );
        }

        return TokenLoginResponse::pendingTwoFactorAuth(
            user: $data->user,
            twoFactorToken: $twoFactorToken,
        );
    }
}
