<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Requests\VerifyMagicLinkRequest;
use App\Modules\Auth\Actions\VerifyMagicLinkAction;
use App\Modules\Auth\DataTransferObjects\VerifyMagicLinkData;
use App\Modules\Auth\Exceptions\UnusableMagicLinkException;
use App\Modules\Auth\Responses\LoginResponse;
use Illuminate\Validation\ValidationException;

class VerifyMagicLinkController
{
    public function __invoke(VerifyMagicLinkRequest $request, VerifyMagicLinkAction $verifyMagicLinkAction): LoginResponse
    {
        try {
            return $verifyMagicLinkAction->execute(
                VerifyMagicLinkData::fromToken(
                    token: $request->validated('token'),
                    email: $request->validated('email'),
                ),
            );
        } catch (UnusableMagicLinkException) {
            throw ValidationException::withMessages([
                'token' => ['The provided magic link is invalid or has expired.'],
            ]);
        }
    }
}
