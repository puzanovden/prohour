<?php
namespace App\Repositories;

use PDO;

class TaskRepository
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function batchInsertTasks(array $tasks): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO tasks (user_id, project_id, name, status, comment, accumulated_time, last_started_at)
                 VALUES (:user_id, :project_id, :name, :status, :comment, :accumulated_time, :last_started_at)'
            );

            foreach ($tasks as $task) {
                $stmt->execute([
                    ':user_id' => $task['user_id'] ?? null,
                    ':project_id' => $task['project_id'] ?? null,
                    ':name' => $task['name'],
                    ':status' => $task['status'] ?? 'paused',
                    ':comment' => $task['comment'] ?? null,
                    ':accumulated_time' => $task['accumulated_time'] ?? 0,
                    ':last_started_at' => $task['last_started_at'] ?? 0,
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function getTasks(): array
    {
        $stmt = $this->db->query(
            'SELECT
                tasks.*,
                users.name AS user_name,
                projects.name AS project_name,
                clients.name AS client_name
             FROM tasks
             LEFT JOIN users ON tasks.user_id = users.id
             LEFT JOIN projects ON tasks.project_id = projects.id
             LEFT JOIN clients ON projects.client_id = clients.id
             ORDER BY tasks.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}