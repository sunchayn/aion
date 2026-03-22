<?php

namespace App\Modules\Auth\OAuthProviders\Enum;

enum OperationTypeEnum: string
{
    case Auth = 'auth';

    case Link = 'link';
}
