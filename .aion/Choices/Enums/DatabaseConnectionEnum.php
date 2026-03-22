<?php

namespace Aion\Choices\Enums;

use Aion\Choices\Enums\Concerns\ExportsOptions;

enum DatabaseConnectionEnum: string
{
    use ExportsOptions;

    case SQLite = 'sqlite';
    case MySQL = 'mysql';
    case MariaDB = 'mariadb';
    case PostgreSQL = 'pgsql';
    case SQLServer = 'sqlsrv';
}
