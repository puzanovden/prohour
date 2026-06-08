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
                'INSERT INTO tasks 
                    (
                        user_id, 
                        project_id, 
                        name, 
                        status, 
                        comment, 
                        accumulated_time, 
                        last_started_at,
                        created_by_user_id,
                        assigned_to_user_id,
                        priority,
                        deadline
                    )
                 VALUES 
                    (
                        :user_id, 
                        :project_id, 
                        :name, 
                        :status, 
                        :comment, 
                        :accumulated_time, 
                        :last_started_at,
                        :created_by_user_id,
                        :assigned_to_user_id,
                        :priority,
                        :deadline
                    )'
            );

            foreach ($tasks as $task) {
                $assignedToUserId = $task['assigned_to_user_id'] ?? $task['user_id'] ?? null;
                $createdByUserId = $task['created_by_user_id'] ?? $task['user_id'] ?? null;

                $stmt->execute([
                    ':user_id' => $assignedToUserId,
                    ':project_id' => $task['project_id'] ?? null,
                    ':name' => $task['name'],
                    ':status' => $task['status'] ?? 'paused',
                    ':comment' => $task['comment'] ?? null,
                    ':accumulated_time' => $task['accumulated_time'] ?? 0,
                    ':last_started_at' => $task['last_started_at'] ?? 0,
                    ':created_by_user_id' => $createdByUserId,
                    ':assigned_to_user_id' => $assignedToUserId,
                    ':priority' => $task['priority'] ?? 'normal',
                    ':deadline' => $task['deadline'] ?? null,
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

    public function createTask(
        int $createdByUserId,
        int $assignedToUserId,
        ?int $projectId,
        string $name,
        string $comment = '',
        string $priority = 'normal',
        ?string $deadline = null
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks 
                (
                    user_id,
                    project_id,
                    name,
                    status,
                    comment,
                    accumulated_time,
                    last_started_at,
                    created_by_user_id,
                    assigned_to_user_id,
                    priority,
                    deadline
                )
             VALUES
                (
                    :user_id,
                    :project_id,
                    :name,
                    :status,
                    :comment,
                    :accumulated_time,
                    :last_started_at,
                    :created_by_user_id,
                    :assigned_to_user_id,
                    :priority,
                    :deadline
                )'
        );

        $stmt->execute([
            ':user_id' => $assignedToUserId,
            ':project_id' => $projectId,
            ':name' => $name,
            ':status' => 'paused',
            ':comment' => $comment,
            ':accumulated_time' => 0,
            ':last_started_at' => 0,
            ':created_by_user_id' => $createdByUserId,
            ':assigned_to_user_id' => $assignedToUserId,
            ':priority' => $priority,
            ':deadline' => $deadline,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getTasks(): array
    {
        $stmt = $this->db->query(
            'SELECT
                tasks.*,

                assigned_users.name AS assigned_user_name,
                created_users.name AS created_user_name,

                projects.name AS project_name,
                projects.status AS project_status,

                clients.name AS client_name

             FROM tasks

             LEFT JOIN users AS assigned_users 
                ON tasks.assigned_to_user_id = assigned_users.id

             LEFT JOIN users AS created_users 
                ON tasks.created_by_user_id = created_users.id

             LEFT JOIN projects 
                ON tasks.project_id = projects.id

             LEFT JOIN clients 
                ON projects.client_id = clients.id

             ORDER BY tasks.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tasks WHERE id = :id LIMIT 1'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        return $task ?: null;
    }

    public function getTasksForUser(int $userId, bool $canSeeAll): array
{
    if ($canSeeAll) {
        return $this->getTasks();
    }

    $stmt = $this->db->prepare(
        'SELECT 
            tasks.*,
            users.name AS assigned_user_name,
            projects.name AS project_name,
            clients.name AS client_name
         FROM tasks
         LEFT JOIN users ON tasks.user_id = users.id
         LEFT JOIN projects ON tasks.project_id = projects.id
         LEFT JOIN clients ON projects.client_id = clients.id
         WHERE tasks.user_id = :user_id
         ORDER BY tasks.id DESC'
    );

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}