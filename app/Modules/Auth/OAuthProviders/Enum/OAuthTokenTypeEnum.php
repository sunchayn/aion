<?php

namespace App\Modules\Auth\OAuthProviders\Enum;

enum OAuthTokenTypeEnum: string
{
    case AuthorizationToken = 'authorization_token';

    case AccessToken = 'access_token';
}
