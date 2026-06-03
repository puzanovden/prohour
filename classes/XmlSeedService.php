<?php
namespace App\Services;

use App\Xml\XmlSeedParser;
use PDO;

class XmlSeedService
{
    private PDO $db;
    private XmlSeedParser $parser;
    private string $seedFilePath;

    public function __construct(PDO $db, XmlSeedParser $parser, string $seedFilePath)
    {
        $this->db = $db;
        $this->parser = $parser;
        $this->seedFilePath = $seedFilePath;
    }

    public function seedIfDatabaseIsEmpty(): void
    {
        if (!$this->isSeedRequired()) {
            return;
        }

        $seedData = $this->parser->parseFile($this->seedFilePath);
        $this->insertSeedData($seedData);
    }

    private function isSeedRequired(): bool
    {
        $usersCount = (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $clientsCount = (int)$this->db->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        $projectsCount = (int)$this->db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        $tasksCount = (int)$this->db->query('SELECT COUNT(*) FROM tasks')->fetchColumn();

        return $usersCount === 0
            && $clientsCount === 0
            && $projectsCount === 0
            && $tasksCount === 0;
    }

    private function insertSeedData(array $seedData): void
    {
        $insertUser = $this->db->prepare(
            'INSERT INTO users (email, password, name) VALUES (:email, :password, :name)'
        );

        $insertClient = $this->db->prepare(
            'INSERT INTO clients (name) VALUES (:name)'
        );

        $insertProject = $this->db->prepare(
            'INSERT INTO projects (client_id, name, description) VALUES (:client_id, :name, :description)'
        );

        $insertTask = $this->db->prepare(
            'INSERT INTO tasks (user_id, project_id, name, status, comment, accumulated_time, last_started_at)
             VALUES (:user_id, :project_id, :name, :status, :comment, :accumulated_time, :last_started_at)'
        );

        foreach ($seedData['users'] as $user) {
            $insertUser->execute([
                ':email' => $user['email'],
                ':password' => $user['password'],
                ':name' => $user['name'],
            ]);

            $userId = (int)$this->db->lastInsertId();

            foreach ($user['projects'] as $project) {
                $insertClient->execute([
                    ':name' => $project['client']['name']
                ]);

                $clientId = (int)$this->db->lastInsertId();

                $insertProject->execute([
                    ':client_id' => $clientId,
                    ':name' => $project['name'],
                    ':description' => $project['description'],
                ]);

                $projectId = (int)$this->db->lastInsertId();

                foreach ($project['tasks'] as $task) {
                    $insertTask->execute([
                        ':user_id' => $userId,
                        ':project_id' => $projectId,
                        ':name' => $task['name'],
                        ':status' => $task['status'],
                        ':comment' => $task['comment'],
                        ':accumulated_time' => $task['accumulated_time'],
                        ':last_started_at' => $task['last_started_at'],
                    ]);
                }
            }
        }
    }
}