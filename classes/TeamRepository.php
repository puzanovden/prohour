<?php

namespace App\Repositories;

use PDO;

class TeamRepository
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function getTeams(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM teams ORDER BY id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTeamById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM teams WHERE id = :id LIMIT 1'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        return $team ?: null;
    }

    public function createTeam(string $name, string $description = ''): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO teams (name, description, created_at)
             VALUES (:name, :description, :created_at)'
        );

        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->lastInsertId();
    }
}