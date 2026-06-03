<?php
namespace App\Database;

require_once __DIR__ . '/XmlSeedParser.php';
require_once __DIR__ . '/XmlSeedService.php';

use App\Services\XmlSeedService;
use App\Xml\XmlSeedParser;
use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;

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

            $this->connection->exec('PRAGMA foreign_keys = ON');

            $this->connection->beginTransaction();

            $this->createTables();
            $this->seedDatabaseFromXmlIfEmpty();

            $this->connection->commit();
        }
        catch (PDOException $e)
        {
            $this->rollbackTransactionIfNeeded();
            $this->showDatabaseError($e);
        }
        catch (\Throwable $e)
        {
            $this->rollbackTransactionIfNeeded();
            $this->showDatabaseError($e);
        }
    }

    private function createTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users
            (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                name TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS clients
            (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS projects
            (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER,
                name TEXT NOT NULL,
                description TEXT,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS tasks
            (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                project_id INTEGER,
                name TEXT NOT NULL,
                status TEXT DEFAULT 'paused',
                comment TEXT,
                accumulated_time INTEGER DEFAULT 0,
                last_started_at INTEGER DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
            );
        ";

        $this->connection->exec($sql);
    }

    private function seedDatabaseFromXmlIfEmpty(): void
    {
        $seedFilePath = __DIR__ . '/../data/seed.xml';
        $seedService = new XmlSeedService(
            $this->connection,
            new XmlSeedParser(),
            $seedFilePath
        );

        $seedService->seedIfDatabaseIsEmpty();
    }

    private function rollbackTransactionIfNeeded(): void
    {
        if ($this->connection && $this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }

    private function showDatabaseError(\Throwable $e): void
    {
        echo "<div style='color:red; font-weight:bold; padding:20px; background:#fce4e4; border:1px solid red;'>";
        echo "Повідомлення про неможливість створення бази даних!<br>";
        echo "Код помилки: " . $e->getCode() . "<br>";

        if ($this->connection) {
            echo "Деталі помилки: " . implode(", ", $this->connection->errorInfo()) . "<br>";
        }

        echo "Системний текст: " . $e->getMessage();
        echo "</div>";
        die();
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }
}