<?php

class Database
{
    private $connection;

    public function connect()
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

            //echo "Database connected successfully<br>";
        }
        catch (PDOException $e)
        {
            die("Connection error: " . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $this->connection = null;

        //echo "Database connection closed<br>";
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function createTables()
        {
            try
            {
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

                //echo "Tables created successfully<br>";
            }
            catch (PDOException $e)
            {
                echo "Table creation error: "
                    . $e->getMessage();
            }
        }
}