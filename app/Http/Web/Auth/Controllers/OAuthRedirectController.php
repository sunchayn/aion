<?php

namespace App\Http\Web\Auth\Controllers;

use App\Http\Web\Auth\Requests\OAuthRedirectRequest;
use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ProvidersFactory;
use Illuminate\Http\RedirectResponse;

class OAuthRedirectController
{
    public function __invoke(
        OAuthRedirectRequest $request,
        string $provider,
        ProvidersFactory $providersFactory,
    ): RedirectResponse {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            abort(404);
        }

        $providerEnum = ProviderEnum::tryFrom($provider);

        abort_unless(
            $providerEnum !== null,
            404,
            'Invalid OAuth provider',
        );

        /** @var OperationTypeEnum $operationType */
        $operationType = $request->enum('operation_type', OperationTypeEnum::class);

        return $providersFactory
            ->make($providerEnum)
            ->redirectToProvider($operationType);
    }
}
