<?php

declare(strict_types=1);

namespace App\Modules\Auth\DataTransferObjects;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;

final readonly class UnlinkSocialAccountData
{
    public function __construct(
        public User $user,
        public ProviderEnum $provider,
    ) {}
}
