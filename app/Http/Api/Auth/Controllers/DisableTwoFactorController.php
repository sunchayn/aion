<?php

declare(strict_types=1);

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Shared\Resources\MessageResource;
use App\Modules\Auth\Actions\DisableTwoFactorAuthAction;
use App\Modules\Shared\Models\User;
use Illuminate\Http\Request;

class DisableTwoFactorController
{
    public function __invoke(Request $request, DisableTwoFactorAuthAction $disable2FAAction): MessageResource
    {
        /** @var User $user */
        $user = $request->user();

        $disable2FAAction->execute($user);

        return MessageResource::make('Two-factor authentication disabled successfully.');
    }
}
