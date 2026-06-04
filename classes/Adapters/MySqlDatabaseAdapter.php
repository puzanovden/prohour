<?php

namespace App\Adapters;

use PDO;

class MySqlDatabaseAdapter implements DatabaseAdapterInterface
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;

    public function __construct(
        string $host = 'localhost',
        string $database = 'prohour',
        string $username = 'root',
        string $password = ''
    ) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";

        $connection = new PDO($dsn, $this->username, $this->password);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }

    public function getName(): string
    {
        return 'MySQL database adapter';
    }
}