<?php

namespace App\Repositories;

use PDO;

class ClientRepository
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function getClients(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM clients ORDER BY id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createClient(
        string $name,
        string $contactPerson,
        string $email,
        string $phone,
        string $description
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO clients 
                (name, contact_person, email, phone, description, created_at)
             VALUES 
                (:name, :contact_person, :email, :phone, :description, :created_at)'
        );

        return $stmt->execute([
            ':name' => $name,
            ':contact_person' => $contactPerson,
            ':email' => $email,
            ':phone' => $phone,
            ':description' => $description,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteClient(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM clients WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function getClientsByTeamId(int $teamId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * 
            FROM clients 
            WHERE team_id = :team_id 
            ORDER BY id DESC'
        );

        $stmt->execute([
            ':team_id' => $teamId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}