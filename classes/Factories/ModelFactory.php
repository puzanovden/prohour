<?php

namespace App\Factories;

use App\Models\ProjectModel;
use App\Models\TaskModel;
use App\Models\UserModel;

class ModelFactory
{
    public static function createTask(array $data): TaskModel
    {
        return new TaskModel(
            isset($data['id']) ? (int)$data['id'] : null,
            isset($data['user_id']) ? (int)$data['user_id'] : null,
            isset($data['project_id']) ? (int)$data['project_id'] : null,
            trim($data['name'] ?? ''),
            $data['status'] ?? 'paused',
            $data['comment'] ?? null,
            isset($data['accumulated_time']) ? (int)$data['accumulated_time'] : 0,
            isset($data['last_started_at']) ? (int)$data['last_started_at'] : 0
        );
    }

    public static function createUser(array $data): UserModel
    {
        return new UserModel(
            isset($data['id']) ? (int)$data['id'] : null,
            trim($data['email'] ?? ''),
            $data['password'] ?? '',
            trim($data['name'] ?? '')
        );
    }

    public static function createProject(array $data): ProjectModel
    {
        return new ProjectModel(
            isset($data['id']) ? (int)$data['id'] : null,
            isset($data['client_id']) ? (int)$data['client_id'] : null,
            trim($data['name'] ?? ''),
            $data['description'] ?? null
        );
    }
}