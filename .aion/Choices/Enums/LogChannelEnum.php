<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum LogChannelEnum: string
{
    use ExportsOptions;

    case Stack = 'stack';
    case Single = 'single';
    case Daily = 'daily';
    case Slack = 'slack';
    case Papertrail = 'papertrail';
    case Stderr = 'stderr';
    case Syslog = 'syslog';
    case ErrorLog = 'errorlog';
    case Null = 'null';
}
