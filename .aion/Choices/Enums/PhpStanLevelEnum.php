<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum PhpStanLevelEnum: string
{
    use ExportsOptions;

    case Level5 = '5';
    case Level6 = '6';
    case Level7 = '7';
    case Level8 = '8';
    case Level9 = '9';
    case Level10 = '10';
}
