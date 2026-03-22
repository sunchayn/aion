<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\ResetPasswordData;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** @final */
class ResetPasswordAction
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(ResetPasswordData $data): bool
    {
        $status = Password::broker()->reset(
            credentials: $data->toArray(),
            callback: fn (User $user, string $newPassword) => $this->afterPasswordReset($user, $newPassword),
        );

        return $status === Password::PASSWORD_RESET;
    }

    private function afterPasswordReset(User $user, string $newPassword): void
    {
        $user->password = $newPassword;
        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->dispatcher->dispatch(new PasswordReset($user));
    }
}
