<?php

namespace App\Http\Api\Auth\Controllers;

use App\Http\Api\Auth\Resources\TwoFactorSetupResource;
use App\Modules\Auth\Actions\EnableTwoFactorAuthAction;
use App\Modules\Shared\Models\User;
use Illuminate\Http\Request;

class EnableTwoFactorController
{
    public function __invoke(Request $request, EnableTwoFactorAuthAction $enable2FAAction): TwoFactorSetupResource
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $enable2FAAction->execute($user);

        return TwoFactorSetupResource::make($payload);
    }
}
