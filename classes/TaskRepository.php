<?php

class TaskRepository
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function createTask($title)
    {
        try
        {
            $sql = "
                    INSERT INTO tasks
                    (
                        name,
                        status,
                        accumulated_time,
                        last_started_at
                    )
                    VALUES
                    (
                        :name,
                        'paused',
                        0,
                        0
                    )
            ";

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':title' => $title
            ]);

   
        }
        catch (PDOException $e)
        {
            echo "Insert error: "
                . $e->getMessage();
        }
    }
    
    public function getTasks()
    {
        try
        {
            $sql = "
                SELECT *
                FROM tasks
                ORDER BY id DESC
            ";

            $stmt = $this->connection->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e)
        {
            echo "Select error: "
                . $e->getMessage();

            return [];
        }
    }

    public function deleteTask($id)
    {
        $sql = "
            DELETE FROM tasks
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);
    }

    public function completeTask($id)
    {
        $sql = "
            UPDATE tasks
            SET status = 'completed'
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);
    }
    public function playTask($id)
    {
        $sql = "
            UPDATE tasks
            SET
                status = 'active',
                last_started_at = :time
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':time' => time(),
            ':id' => $id
        ]);
    }
    public function pauseTask($id)
    {
        $task = $this->getTaskById($id);

        $elapsed =
            $task['accumulated_time']
            +
            (time() - $task['last_started_at']);

        $sql = "
            UPDATE tasks
            SET
                status = 'paused',
                accumulated_time = :time
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':time' => $elapsed,
            ':id' => $id
        ]);
    }
    public function getTaskById($id)
    {
        $sql = "
            SELECT *
            FROM tasks
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function editTask($id, $name)
    {
        $sql = "
            UPDATE tasks
            SET name = :name
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':name' => $name,
            ':id' => $id
        ]);
    }
}


