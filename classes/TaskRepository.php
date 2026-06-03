<?php
namespace App\Repositories;

use App\Database\Database;
use PDO;

class TaskRepository {
    private PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    public function batchInsertTasks(array $tasks): bool {
        try {
            $this->db->beginTransaction(); 

            $stmt = $this->db->prepare("INSERT INTO tasks (name, status, accumulated_time, last_started_at) VALUES (:name, :status, 0, 0)");
            
            foreach ($tasks as $task) {
                $stmt->execute([
                    ':name' => $task['name'],
                    ':status' => $task['status'] ?? 'paused'
                ]);
            }

            $this->db->commit(); 
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack(); 
            return false;
        }
    }

    public function getTasks(): array {
        $stmt = $this->db->query("SELECT * FROM tasks ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
}