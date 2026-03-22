<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\UnlinkSocialAccountData;

/** @final */
class UnlinkSocialAccountAction
{
    public function execute(UnlinkSocialAccountData $data): void
    {
        $data->user->oauthAccounts()
            ->where('provider', $data->provider)
            ->delete();
    }
}
