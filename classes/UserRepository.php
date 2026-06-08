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
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password, name) VALUES (:email, :password, :name)'
        );

        return $stmt->execute([
            ':email' => $email,
            ':password' => $passwordHash,
            ':name' => $name,
        ]);
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updateUserName(int $id, string $name): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name = :name WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
        ]);
    }

    public function updateUserPassword(int $id, string $password): bool
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'UPDATE users SET password = :password WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':password' => $passwordHash,
        ]);
    }

    public function getUsers(): array
    {
        $stmt = $this->db->query('SELECT * FROM users ORDER BY id DESC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUserAvatar(int $id, string $avatar): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET avatar = :avatar WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':avatar' => $avatar,
        ]);
    }

    public function updateUserRoleAndTeam(int $userId, string $role, int $teamId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
            SET role = :role,
                team_id = :team_id
            WHERE id = :id'
        );

        return $stmt->execute([
            ':role' => $role,
            ':team_id' => $teamId,
            ':id' => $userId,
        ]);
    }

    public function deleteUserById(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM users WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $userId,
        ]);
    }



    public function getUserByGoogleId(string $googleId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE google_id = :google_id LIMIT 1'
        );

        $stmt->execute([
            ':google_id' => $googleId,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function linkGoogleToUser(int $userId, string $googleId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users
            SET google_id = :google_id,
                auth_provider = :auth_provider
            WHERE id = :id"
        );

        return $stmt->execute([
            ':google_id' => $googleId,
            ':auth_provider' => 'google',
            ':id' => $userId,
        ]);
    }

    public function createGoogleUser(
        string $email,
        string $name,
        string $googleId,
        string $avatar = '',
        int $teamId = 1,
        string $role = 'employee'
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users 
                (email, password, name, avatar, role, team_id, google_id, auth_provider)
            VALUES 
                (:email, :password, :name, :avatar, :role, :team_id, :google_id, :auth_provider)"
        );

        $stmt->execute([
            ':email' => $email,
            ':password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            ':name' => $name,
            ':avatar' => $avatar,
            ':role' => $role,
            ':team_id' => $teamId,
            ':google_id' => $googleId,
            ':auth_provider' => 'google',
        ]);

        return (int)$this->db->lastInsertId();
    }
}