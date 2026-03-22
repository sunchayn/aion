<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\ContinueWithOAuthData;
use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\Responses\LoginResponse;
use App\Modules\Shared\Models\User;
use App\Modules\Users\DataTransferObjects\CreateUserData;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

/** @final */
class ContinueWithOAuthAction
{
    public function __construct(
        private readonly LoginAction $loginAction,
        private readonly RegisterUserAction $registerUserAction,
    ) {}

    /**
     * @throws UnableToContinueWithOAuthException
     * @throws Throwable
     */
    public function execute(ContinueWithOAuthData $data): LoginResponse
    {
        throw_if(
            ! $data->socialiteUser instanceof SocialiteUser,
            UnableToContinueWithOAuthException::class,
        );

        if ($data->alreadyLinked) {
            throw_if(
                ! $data->user instanceof User,
                UnableToContinueWithOAuthException::class,
            );

            return $this->loginAction->execute(
                new LoginData(
                    user: $data->user,
                    remember: $data->remember,
                )
            );
        }

        $user = $data->user ?: $this->registerUser(
            socialiteUser: $data->socialiteUser,
        );

        $this->ensureOAuthAccountIsLinked(
            user: $user,
            socialiteUser: $data->socialiteUser,
            provider: $data->provider,
        );

        return $this->loginAction->execute(
            new LoginData(
                user: $user,
                remember: $data->remember,
            )
        );
    }

    private function registerUser(SocialiteUser $socialiteUser): User
    {
        $email = $socialiteUser->getEmail();

        throw_if(
            empty($email),
            UnableToContinueWithOAuthException::class,
        );

        $user = $this->registerUserAction->execute(
            new CreateUserData(
                name: $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? '',
                email: $email,
                password: Str::random(32),
            ),
        );

        $user->markEmailAsVerified();

        return $user;
    }

    private function ensureOAuthAccountIsLinked(User $user, SocialiteUser $socialiteUser, ProviderEnum $provider): void
    {
        UserOAuthAccount::query()->updateOrCreate([
            'user_id' => $user->getAuthIdentifier(),
            'provider' => $provider,
            'provider_user_id' => $socialiteUser->getId(),
        ], [
            'provider_avatar' => $socialiteUser->getAvatar(),
            'access_token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
        ]);
    }
}
