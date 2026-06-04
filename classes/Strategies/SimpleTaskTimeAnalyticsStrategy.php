<?php

namespace App\Strategies;

class SimpleTaskTimeAnalyticsStrategy implements TaskTimeAnalyticsStrategyInterface
{
    public function calculate(array $tasks): array
    {
        $totalSeconds = 0;

        foreach ($tasks as $task) {
            $totalSeconds += $this->getActualTaskSeconds($task);
        }

        return [
            'type' => 'simple',
            'total_seconds' => $totalSeconds,
            'tasks_count' => count($tasks),
        ];
    }

    public function getName(): string
    {
        return 'Загальна аналітика часу';
    }

    private function getActualTaskSeconds(array $task): int
    {
        $seconds = (int)($task['accumulated_time'] ?? 0);

        if (($task['status'] ?? '') === 'active') {
            $seconds += time() - (int)($task['last_started_at'] ?? time());
        }

        return $seconds;
    }
}