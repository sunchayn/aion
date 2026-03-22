<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\Responses\SessionLoginResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Contracts\Events\Dispatcher;

/** @final */
class StatefulLoginAction
{
    public function __construct(
        #[Tag('hooks.login.before')]
        private readonly iterable $beforeLoginHooks,
        private readonly AuthManager $authManager,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(LoginData $data): SessionLoginResponse
    {
        foreach ($this->beforeLoginHooks as $hook) {
            $result = $hook($data);

            if ($result instanceof SessionLoginResponse) {
                return $result;
            }
        }

        $this->authManager->guard($data->guard)->login($data->user, $data->remember);

        $this->dispatcher->dispatch(
            new Login(
                guard: $data->guard,
                user: $data->user,
                remember: $data->remember,
            ),
        );

        return new SessionLoginResponse(
            user: $data->user,
            twoFactor: false,
        );
    }
}
