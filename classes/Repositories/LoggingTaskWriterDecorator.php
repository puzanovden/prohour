<?php

namespace App\Repositories;

use App\Models\TaskModel;

class LoggingTaskWriterDecorator implements TaskWriterInterface
{
    private TaskWriterInterface $innerWriter;
    private string $logFilePath;

    public function __construct(TaskWriterInterface $innerWriter, string $logFilePath)
    {
        $this->innerWriter = $innerWriter;
        $this->logFilePath = $logFilePath;
    }

    public function save(TaskModel $task): int
    {
        $this->logBeforeSave($task);

        return $this->innerWriter->save($task);
    }

    private function logBeforeSave(TaskModel $task): void
    {
        $directory = dirname($this->logFilePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $line = sprintf(
            "[%s] Перед записом задачі в БД: user_id=%s; project_id=%s; name=%s; status=%s; comment=%s\n",
            date('Y-m-d H:i:s'),
            $task->userId ?? 'NULL',
            $task->projectId ?? 'NULL',
            $task->name,
            $task->status,
            $task->comment ?? ''
        );

        file_put_contents($this->logFilePath, $line, FILE_APPEND);
    }
}