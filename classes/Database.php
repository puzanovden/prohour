<?php
namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private $connection;

    public function __construct()
    {
        try
        {

            $this->connection = new PDO(
                "sqlite:" . __DIR__ . "/../database/prohour.db"
            );

            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->connection->beginTransaction();

            $sql = "
                CREATE TABLE IF NOT EXISTS projects
                (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT
                );

                CREATE TABLE IF NOT EXISTS tasks
                (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    status TEXT DEFAULT 'paused',
                    accumulated_time INTEGER DEFAULT 0,
                    last_started_at INTEGER DEFAULT 0
                );
            ";

            $this->connection->exec($sql);
            $this->connection->commit();
        }
        catch (PDOException $e)
        {
            if ($this->connection && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            echo "<div style='color:red; font-weight:bold; padding:20px; background:#fce4e4; border:1px solid red;'>";
            echo "Повідомлення про неможливість створення бази даних!<br>";
            echo "Код помилки (SQLSTATE): " . $e->getCode() . "<br>";
            if ($this->connection) {
                echo "Деталі помилки: " . implode(", ", $this->connection->errorInfo()) . "<br>";
            }
            echo "Системний текст: " . $e->getMessage();
            echo "</div>";
            die();
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function disconnect()
    {
        $this->connection = null;
    }
}