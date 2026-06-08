<?php

namespace App\Repositories;

use PDO;

class ProjectRepository
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function getProjects(): array
    {
        $stmt = $this->db->query(
            'SELECT 
                projects.*,
                clients.name AS client_name
             FROM projects
             LEFT JOIN clients ON projects.client_id = clients.id
             ORDER BY projects.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectsByTeamId(int $teamId): array
    {
        $stmt = $this->db->prepare(
            'SELECT 
                projects.*,
                clients.name AS client_name
             FROM projects
             LEFT JOIN clients ON projects.client_id = clients.id
             WHERE projects.team_id = :team_id
             ORDER BY projects.id DESC'
        );

        $stmt->execute([
            ':team_id' => $teamId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectsByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * 
             FROM projects 
             WHERE client_id = :client_id 
             ORDER BY name ASC'
        );

        $stmt->execute([
            ':client_id' => $clientId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM projects WHERE id = :id LIMIT 1'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    public function createProject(
        int $teamId,
        ?int $clientId,
        string $name,
        string $description = '',
        string $status = 'active'
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO projects 
                (team_id, client_id, name, description, status, created_at)
             VALUES 
                (:team_id, :client_id, :name, :description, :status, :created_at)'
        );

        $stmt->execute([
            ':team_id' => $teamId,
            ':client_id' => $clientId,
            ':name' => $name,
            ':description' => $description,
            ':status' => $status,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function deleteProject(int $id, int $teamId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM projects 
             WHERE id = :id 
               AND team_id = :team_id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':team_id' => $teamId,
        ]);
    }

    public function findProjectByNameAndClient(int $teamId, ?int $clientId, string $name): ?array
    {
        if ($clientId === null) {
            $stmt = $this->db->prepare(
                'SELECT * 
                 FROM projects 
                 WHERE team_id = :team_id
                   AND client_id IS NULL
                   AND LOWER(name) = LOWER(:name)
                 LIMIT 1'
            );

            $stmt->execute([
                ':team_id' => $teamId,
                ':name' => $name,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT * 
                 FROM projects 
                 WHERE team_id = :team_id
                   AND client_id = :client_id
                   AND LOWER(name) = LOWER(:name)
                 LIMIT 1'
            );

            $stmt->execute([
                ':team_id' => $teamId,
                ':client_id' => $clientId,
                ':name' => $name,
            ]);
        }

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    public function getOrCreateProject(int $clientId, string $name): int
    {
        $teamId = 1;

        $project = $this->findProjectByNameAndClient($teamId, $clientId, $name);

        if ($project) {
            return (int)$project['id'];
        }

        return $this->createProject($teamId, $clientId, $name);
    }

    public function getOrCreateProjectForTeam(int $teamId, ?int $clientId, string $name): int
    {
        $project = $this->findProjectByNameAndClient($teamId, $clientId, $name);

        if ($project) {
            return (int)$project['id'];
        }

        return $this->createProject($teamId, $clientId, $name);
    }
}