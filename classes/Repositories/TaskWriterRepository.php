<?php

namespace App\Repositories;

use App\Models\TaskModel;
use PDO;

class TaskWriterRepository implements TaskWriterInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(TaskModel $task): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks (user_id, project_id, name, status, comment, accumulated_time, last_started_at)
             VALUES (:user_id, :project_id, :name, :status, :comment, :accumulated_time, :last_started_at)'
        );

        $stmt->execute([
            ':user_id' => $task->userId,
            ':project_id' => $task->projectId,
            ':name' => $task->name,
            ':status' => $task->status,
            ':comment' => $task->comment,
            ':accumulated_time' => $task->accumulatedTime,
            ':last_started_at' => $task->lastStartedAt,
        ]);

        return (int)$this->db->lastInsertId();
    }
}