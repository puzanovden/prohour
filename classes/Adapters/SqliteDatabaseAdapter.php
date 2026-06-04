<?php

namespace App\Adapters;

use App\Database\Database;
use PDO;

class SqliteDatabaseAdapter implements DatabaseAdapterInterface
{
    public function connect(): PDO
    {
        $database = new Database();

        return $database->getConnection();
    }

    public function getName(): string
    {
        return 'SQLite database adapter';
    }
}