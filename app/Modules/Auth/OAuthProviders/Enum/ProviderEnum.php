<?php

namespace App\Modules\Auth\OAuthProviders\Enum;

enum ProviderEnum: string
{
    case Google = 'google';

    case Apple = 'apple';

    case GitHub = 'github';
}
