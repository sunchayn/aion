<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\LoginData;
use App\Modules\Auth\DataTransferObjects\VerifyMagicLinkData;
use App\Modules\Auth\Exceptions\UnusableMagicLinkException;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Responses\LoginResponse;
use Throwable;

/** @final */
class VerifyMagicLinkAction
{
    public function __construct(
        private readonly LoginAction $loginAction,
    ) {}

    /**
     * @throws UnusableMagicLinkException
     * @throws Throwable
     */
    public function execute(VerifyMagicLinkData $data): LoginResponse
    {
        throw_if(
            ! $data->magicLink instanceof MagicLink,
            UnusableMagicLinkException::class,
        );

        throw_if(
            $data->magicLink->user === null,
            UnusableMagicLinkException::class,
        );

        throw_if(
            $data->magicLink->expires_at->isPast(),
            UnusableMagicLinkException::class,
        );

        throw_if(
            $data->magicLink->used_at !== null,
            UnusableMagicLinkException::class,
        );

        $data->magicLink->update(['used_at' => now()]);

        return $this->loginAction->execute(
            new LoginData(
                user: $data->magicLink->user,
                guard: $data->guard,
                remember: $data->remember,
            )
        );
    }
}
