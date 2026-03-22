<?php

namespace App\Modules\Auth\Responses;

use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** @final */
class TokenLoginResponse implements LoginResponse
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $authToken,
        public readonly ?string $refreshToken = null,
        public readonly ?string $twoFactorToken = null,
        public readonly bool $twoFactor = false,
    ) {}

    public static function pendingTwoFactorAuth(User $user, string $twoFactorToken): self
    {
        return new self(
            user: $user,
            authToken: null,
            twoFactorToken: $twoFactorToken,
            twoFactor: true,
        );
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        $this->user->loadMissing('oauthAccounts');

        $data = [
            'user_id' => $this->user->id,
            'two_factor' => $this->twoFactor,
            'email' => $this->user->email,
            'name' => $this->user->name,
            'email_verified' => $this->user->hasVerifiedEmail(),
            'avatar_url' => $this->user->oauthAccounts->value('provider_avatar'),
            'oauth_providers' => $this->user->oauthAccounts->pluck('provider.value')->all(),
        ];

        if ($this->authToken !== null) {
            $data['auth_token'] = $this->authToken;
        }

        if ($this->refreshToken !== null) {
            $data['refresh_token'] = $this->refreshToken;
        }

        if ($this->twoFactorToken !== null) {
            $data['two_factor_token'] = $this->twoFactorToken;
        }

        return new JsonResponse([
            'data' => $data,
        ]);
    }
}
