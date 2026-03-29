<?php

namespace Aion\Choices\Enums;

enum ConfigKeyEnum: string
{
    case ApiType = 'api-type';
    case OAuth = 'oauth';
    case OAuthProviders = 'oauth-providers';
    case LoggingStructure = 'logging-structure';
    case Logs = 'logs';
    case DB = 'db';
    case PhpStan = 'phpstan';
    case Frontend = 'frontend';
    case AgenticAi = 'agentic-ai';
}
