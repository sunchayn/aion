<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\SendMagicLinkRequest;
use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\SendMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\SendMagicLinkData;

class SendMagicLinkController
{
    public function __invoke(
        SendMagicLinkRequest $request,
        SendMagicLinkAction $sendMagicLinkAction,
    ): MessageResource {
        $sendMagicLinkAction->execute(
            SendMagicLinkData::fromEmail($request->validated('email')),
        );

        return MessageResource::make('If an account exists, a magic link has been sent.');
    }
}
