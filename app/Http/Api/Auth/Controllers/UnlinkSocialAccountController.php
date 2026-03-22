<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\UnlinkSocialAccountRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\UnlinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\UnlinkSocialAccountData;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;

class UnlinkSocialAccountController
{
    public function __invoke(
        UnlinkSocialAccountRequest $request,
        UnlinkSocialAccountAction $unlinkSocialAccountAction,
    ): MessageResource {
        /** @var User $user */
        $user = $request->user();

        $unlinkSocialAccountAction->execute(
            new UnlinkSocialAccountData(
                user: $user,
                provider: ProviderEnum::from($request->validated('provider')),
            ),
        );

        return new MessageResource('Account unlinked successfully.');
    }
}
