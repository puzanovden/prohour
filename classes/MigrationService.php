<?php

namespace App\Services;

use PDO;

class MigrationService
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function run(): void
    {
        $this->createTeamsTable();

        $this->migrateUsers();
        $this->migrateClients();
        $this->migrateProjects();
        $this->migrateTasks();

        $this->createUploadFolders();
        $this->syncOldTaskUserFields();
        $this->syncDefaultTeam();

        $this->ensureGlobalAdmin();
    }

    private function createTeamsTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS teams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                created_at TEXT
            )"
        );

        $stmt = $this->db->query("SELECT COUNT(*) FROM teams");
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $insert = $this->db->prepare(
                "INSERT INTO teams (name, description, created_at)
                 VALUES (:name, :description, :created_at)"
            );

            $insert->execute([
                ':name' => 'ProHour Team',
                ':description' => 'Базова команда для існуючих користувачів і даних системи.',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function migrateUsers(): void
    {
        $this->addColumnIfNotExists('users', 'avatar', 'TEXT');
        $this->addColumnIfNotExists('users', 'role', "TEXT DEFAULT 'employee'");
        $this->addColumnIfNotExists('users', 'team_id', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('users', 'google_id', 'TEXT');
        $this->addColumnIfNotExists('users', 'auth_provider', "TEXT DEFAULT 'local'");
    }

    private function migrateClients(): void
    {
        $this->addColumnIfNotExists('clients', 'contact_person', 'TEXT');
        $this->addColumnIfNotExists('clients', 'email', 'TEXT');
        $this->addColumnIfNotExists('clients', 'phone', 'TEXT');
        $this->addColumnIfNotExists('clients', 'description', 'TEXT');
        $this->addColumnIfNotExists('clients', 'created_at', 'TEXT');
        $this->addColumnIfNotExists('clients', 'team_id', 'INTEGER DEFAULT 1');
    }

    private function migrateProjects(): void
    {
        $this->addColumnIfNotExists('projects', 'status', "TEXT DEFAULT 'active'");
        $this->addColumnIfNotExists('projects', 'created_at', 'TEXT');
        $this->addColumnIfNotExists('projects', 'team_id', 'INTEGER DEFAULT 1');
    }

    private function migrateTasks(): void
    {
        $this->addColumnIfNotExists('tasks', 'created_by_user_id', 'INTEGER');
        $this->addColumnIfNotExists('tasks', 'assigned_to_user_id', 'INTEGER');
        $this->addColumnIfNotExists('tasks', 'priority', "TEXT DEFAULT 'normal'");
        $this->addColumnIfNotExists('tasks', 'deadline', 'TEXT');
        $this->addColumnIfNotExists('tasks', 'team_id', 'INTEGER DEFAULT 1');
    }

    private function addColumnIfNotExists(string $tableName, string $columnName, string $columnDefinition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->db->exec(
            "ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$columnDefinition}"
        );
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $stmt = $this->db->query("PRAGMA table_info({$tableName})");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                return true;
            }
        }

        return false;
    }

    private function createUploadFolders(): void
    {
        $uploadDir = __DIR__ . '/../uploads';
        $avatarDir = __DIR__ . '/../uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }
    }

    private function syncOldTaskUserFields(): void
    {
        if (!$this->columnExists('tasks', 'created_by_user_id')) {
            return;
        }

        if (!$this->columnExists('tasks', 'assigned_to_user_id')) {
            return;
        }

        $this->db->exec(
            "UPDATE tasks 
             SET created_by_user_id = user_id 
             WHERE created_by_user_id IS NULL"
        );

        $this->db->exec(
            "UPDATE tasks 
             SET assigned_to_user_id = user_id 
             WHERE assigned_to_user_id IS NULL"
        );
    }

    private function syncDefaultTeam(): void
    {
        $defaultTeamId = $this->getDefaultTeamId();

        $this->db->exec(
            "UPDATE users 
             SET team_id = {$defaultTeamId} 
             WHERE team_id IS NULL OR team_id = 0"
        );

        $this->db->exec(
            "UPDATE clients 
             SET team_id = {$defaultTeamId} 
             WHERE team_id IS NULL OR team_id = 0"
        );

        $this->db->exec(
            "UPDATE projects 
             SET team_id = {$defaultTeamId} 
             WHERE team_id IS NULL OR team_id = 0"
        );

        $this->db->exec(
            "UPDATE tasks 
             SET team_id = {$defaultTeamId} 
             WHERE team_id IS NULL OR team_id = 0"
        );
    }

    private function getDefaultTeamId(): int
    {
        $stmt = $this->db->query(
            "SELECT id FROM teams ORDER BY id ASC LIMIT 1"
        );

        $teamId = (int)$stmt->fetchColumn();

        return $teamId > 0 ? $teamId : 1;
    }

    private function ensureGlobalAdmin(): void
{
    $this->addColumnIfNotExists('users', 'role', "TEXT DEFAULT 'employee'");
    $this->addColumnIfNotExists('users', 'team_id', 'INTEGER DEFAULT 1');
    $this->addColumnIfNotExists('users', 'avatar', 'TEXT');

    $stmt = $this->db->prepare(
        "SELECT id FROM users WHERE email = :email LIMIT 1"
    );

    $stmt->execute([
        ':email' => 'admin@prohour.local',
    ]);

    $adminId = $stmt->fetchColumn();

    $passwordHash = password_hash('admin', PASSWORD_DEFAULT);

    if ($adminId) {
        $update = $this->db->prepare(
            "UPDATE users
             SET password = :password,
                 name = :name,
                 role = :role,
                 team_id = :team_id
             WHERE id = :id"
        );

        $update->execute([
            ':password' => $passwordHash,
            ':name' => 'Global Admin',
            ':role' => 'admin',
            ':team_id' => 1,
            ':id' => $adminId,
        ]);

        return;
    }

    $insert = $this->db->prepare(
        "INSERT INTO users (email, password, name, role, team_id, avatar)
         VALUES (:email, :password, :name, :role, :team_id, :avatar)"
    );

    $insert->execute([
        ':email' => 'admin@prohour.local',
        ':password' => $passwordHash,
        ':name' => 'Global Admin',
        ':role' => 'admin',
        ':team_id' => 1,
        ':avatar' => '',
    ]);
}
}