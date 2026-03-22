<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\LinkSocialAccountRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\LinkSocialAccountAction;
use App\Modules\Auth\DataTransferObjects\LinkSocialAccountData;
use App\Modules\Auth\Exceptions\UnableToContinueWithOAuthException;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Illuminate\Validation\ValidationException;

class LinkSocialAccountController
{
    public function __invoke(
        LinkSocialAccountRequest $request,
        LinkSocialAccountAction $linkSocialAccountAction,
    ): MessageResource {
        /** @var User $user */
        $user = $request->user();

        try {
            $linkSocialAccountAction->execute(
                LinkSocialAccountData::fromToken(
                    authenticatedUser: $user,
                    provider: ProviderEnum::from($request->validated('provider')),
                    token: $request->getOAuthToken(),
                ),
            );
        } catch (UnableToContinueWithOAuthException) {
            throw ValidationException::withMessages([
                'token' => 'Unable to link account using this method. Please try again or use a different method.',
            ]);
        }

        return new MessageResource('Account linked successfully.');
    }
}
