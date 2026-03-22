<?php

namespace App\Http\Api\Auth\Controllers;

use App\Modules\Auth\Actions\RegenerateTwoFactorRecoveryCodesAction;
use App\Modules\Shared\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegenerateTwoFactorRecoveryCodesController
{
    public function __invoke(
        Request $request,
        RegenerateTwoFactorRecoveryCodesAction $regenerateCodesAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $recoveryCodes = $regenerateCodesAction->execute($user);

        if ($recoveryCodes === null) {
            return response()->json([
                'message' => 'Two-factor authentication must be enabled to regenerate recovery codes.',
            ], 400);
        }

        return response()->json([
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
            'message' => 'Recovery codes have been successfully regenerated.',
        ]);
    }
}
