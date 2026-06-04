<?php

namespace App\Repositories;

use App\Models\TaskModel;

interface TaskWriterInterface
{
    public function save(TaskModel $task): int;
}