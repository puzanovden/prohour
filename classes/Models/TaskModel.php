<?php

namespace App\Models;

class TaskModel
{
    public ?int $id;
    public ?int $userId;
    public ?int $projectId;
    public string $name;
    public string $status;
    public ?string $comment;
    public int $accumulatedTime;
    public int $lastStartedAt;

    public function __construct(
        ?int $id,
        ?int $userId,
        ?int $projectId,
        string $name,
        string $status = 'paused',
        ?string $comment = null,
        int $accumulatedTime = 0,
        int $lastStartedAt = 0
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->projectId = $projectId;
        $this->name = $name;
        $this->status = $status;
        $this->comment = $comment;
        $this->accumulatedTime = $accumulatedTime;
        $this->lastStartedAt = $lastStartedAt;
    }
}