<?php

namespace App\Strategies;

class StatusGroupedTaskTimeAnalyticsStrategy implements TaskTimeAnalyticsStrategyInterface
{
    public function calculate(array $tasks): array
    {
        $result = [
            'type' => 'status_grouped',
            'groups' => [
                'active' => 0,
                'paused' => 0,
                'completed' => 0,
                'other' => 0,
            ],
        ];

        foreach ($tasks as $task) {
            $status = $task['status'] ?? 'other';

            if (!isset($result['groups'][$status])) {
                $status = 'other';
            }

            $result['groups'][$status] += $this->getActualTaskSeconds($task);
        }

        return $result;
    }

    public function getName(): string
    {
        return 'Аналітика часу за статусами задач';
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