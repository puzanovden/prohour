<?php
namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function createUser(string $email, string $password, string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password, name) VALUES (:email, :password, :name)"
        );

        return $stmt->execute([
            ':email' => $email,
            ':password' => $password,
            ':name' => $name,
        ]);
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function getUsers(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}