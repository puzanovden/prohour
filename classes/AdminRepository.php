<?php

namespace App\Repositories;

use PDO;

class AdminRepository
{
    private PDO $db;

    private array $allowedTables = [
        'users',
        'teams',
        'clients',
        'projects',
        'tasks',
    ];

    private array $blockedColumns = [
        'id',
    ];

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function getAllowedTables(): array
    {
        return $this->allowedTables;
    }

    public function isAllowedTable(string $table): bool
    {
        return in_array($table, $this->allowedTables, true);
    }

    public function getTableColumns(string $table): array
    {
        if (!$this->isAllowedTable($table)) {
            return [];
        }

        $stmt = $this->db->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $columns ?: [];
    }

    public function getEditableColumns(string $table): array
    {
        $columns = $this->getTableColumns($table);

        return array_values(array_filter($columns, function ($column) {
            return !in_array($column['name'], $this->blockedColumns, true);
        }));
    }

    public function getRows(string $table, int $limit = 100): array
    {
        if (!$this->isAllowedTable($table)) {
            return [];
        }

        $limit = max(1, min($limit, 300));

        $stmt = $this->db->query(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT {$limit}"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRowById(string $table, int $id): ?array
    {
        if (!$this->isAllowedTable($table)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$table} WHERE id = :id LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createRow(string $table, array $data): bool
    {
        if (!$this->isAllowedTable($table)) {
            return false;
        }

        $editableColumns = $this->getEditableColumns($table);
        $allowedColumnNames = array_column($editableColumns, 'name');

        $filteredData = [];

        foreach ($allowedColumnNames as $columnName) {
            if (array_key_exists($columnName, $data)) {
                $filteredData[$columnName] = $this->prepareValue($table, $columnName, $data[$columnName]);
            }
        }

        if (empty($filteredData)) {
            return false;
        }

        if ($table === 'users' && isset($filteredData['password']) && $filteredData['password'] !== '') {
            $filteredData['password'] = password_hash($filteredData['password'], PASSWORD_DEFAULT);
        }

        $columns = array_keys($filteredData);
        $placeholders = array_map(fn($column) => ':' . $column, $columns);

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);

        $params = [];

        foreach ($filteredData as $column => $value) {
            $params[':' . $column] = $value;
        }

        return $stmt->execute($params);
    }

    public function updateRow(string $table, int $id, array $data): bool
    {
        if (!$this->isAllowedTable($table)) {
            return false;
        }

        $editableColumns = $this->getEditableColumns($table);
        $allowedColumnNames = array_column($editableColumns, 'name');

        $filteredData = [];

        foreach ($allowedColumnNames as $columnName) {
            if (array_key_exists($columnName, $data)) {
                $filteredData[$columnName] = $this->prepareValue($table, $columnName, $data[$columnName]);
            }
        }

        if (empty($filteredData)) {
            return false;
        }

        if ($table === 'users' && array_key_exists('password', $filteredData)) {
            if (trim((string)$filteredData['password']) === '') {
                unset($filteredData['password']);
            } else {
                $filteredData['password'] = password_hash($filteredData['password'], PASSWORD_DEFAULT);
            }
        }

        if (empty($filteredData)) {
            return false;
        }

        $setParts = [];

        foreach (array_keys($filteredData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':id' => $id,
        ];

        foreach ($filteredData as $column => $value) {
            $params[':' . $column] = $value;
        }

        return $stmt->execute($params);
    }

    public function deleteRow(string $table, int $id): bool
    {
        if (!$this->isAllowedTable($table)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "DELETE FROM {$table} WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    private function prepareValue(string $table, string $column, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if (str_ends_with($column, '_id')) {
            return (int)$value;
        }

        if (in_array($column, ['accumulated_time', 'last_started_at'], true)) {
            return (int)$value;
        }

        return $value;
    }
}