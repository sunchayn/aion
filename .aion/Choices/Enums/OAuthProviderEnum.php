<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum OAuthProviderEnum: string
{
    use ExportsOptions;

    case Google = 'google';

    case GitHub = 'github';

    case Apple = 'apple';
}
