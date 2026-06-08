<?php

namespace App\Controllers;

use App\Adapters\DatabaseAdapterInterface;
use App\Core\View;
use App\Repositories\TaskRepository;
use App\Strategies\SimpleTaskTimeAnalyticsStrategy;
use App\Strategies\StatusGroupedTaskTimeAnalyticsStrategy;
use App\Strategies\TaskTimeAnalyticsStrategyInterface;

class AnalyticsController
{
    private DatabaseAdapterInterface $databaseAdapter;

    public function __construct(DatabaseAdapterInterface $databaseAdapter)
    {
        $this->databaseAdapter = $databaseAdapter;
    }

    public function index(): void
    {
        $db = $this->databaseAdapter->connect();
        $taskRepository = new TaskRepository($db);

        $tasks = $taskRepository->getTasksForUser(
            (int)($_SESSION['user_id'] ?? 0),
            in_array($_SESSION['user_role'] ?? 'employee', ['admin', 'manager'], true)
        );

        $strategy = $this->resolveStrategy($_GET['strategy'] ?? 'simple');
        $analytics = $strategy->calculate($tasks);

        $summary = $this->buildSummary($tasks);
        $byUsers = $this->groupTasksByField($tasks, 'assigned_user_name', 'Не призначено');
        $byProjects = $this->groupTasksByField($tasks, 'project_name', 'Без проєкту');
        $byClients = $this->groupTasksByField($tasks, 'client_name', 'Без клієнта');
        $byStatuses = $this->groupTasksByField($tasks, 'status', 'unknown');
        $byPriorities = $this->groupTasksByField($tasks, 'priority', 'normal');

        $selectedDate = $_GET['date'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = date('Y-m-d');
        }

        $timelineEvents = $this->readTimelineEvents(__DIR__ . '/../../data/task-actions.xml', $tasks);
        $timelineEvents = $this->filterTimelineEventsByTasks($timelineEvents, $tasks);

        $timelineStats = $this->buildTimelineStats($timelineEvents);
        $workTimeline = $this->buildWorkTimeline($timelineEvents, $selectedDate);

        View::render('analytics/index', [
            'title' => 'Аналітика | ProHour',
            'adapterName' => $this->databaseAdapter->getName(),
            'strategyName' => $strategy->getName(),
            'selectedStrategy' => $_GET['strategy'] ?? 'simple',
            'analytics' => $analytics,
            'tasks' => $tasks,
            

            'summary' => $summary,
            'byUsers' => $byUsers,
            'byProjects' => $byProjects,
            'byClients' => $byClients,
            'byStatuses' => $byStatuses,
            'byPriorities' => $byPriorities,

            'timelineEvents' => $timelineEvents,
            'timelineStats' => $timelineStats,
            'workTimeline' => $workTimeline,
            'selectedDate' => $selectedDate,
            'prevDate' => date('Y-m-d', strtotime($selectedDate . ' -1 day')),
            'nextDate' => date('Y-m-d', strtotime($selectedDate . ' +1 day')),
        ]);
    }

    private function resolveStrategy(string $strategyName): TaskTimeAnalyticsStrategyInterface
    {
        if ($strategyName === 'status') {
            return new StatusGroupedTaskTimeAnalyticsStrategy();
        }

        return new SimpleTaskTimeAnalyticsStrategy();
    }

    private function buildSummary(array $tasks): array
    {
        $totalTasks = count($tasks);
        $activeTasks = 0;
        $pausedTasks = 0;
        $completedTasks = 0;
        $totalSeconds = 0;

        foreach ($tasks as $task) {
            $status = $task['status'] ?? 'paused';

            if ($status === 'active') {
                $activeTasks++;
            }

            if ($status === 'paused') {
                $pausedTasks++;
            }

            if ($status === 'completed') {
                $completedTasks++;
            }

            $totalSeconds += $this->getTaskActualSeconds($task);
        }

        return [
            'total_tasks' => $totalTasks,
            'active_tasks' => $activeTasks,
            'paused_tasks' => $pausedTasks,
            'completed_tasks' => $completedTasks,
            'total_seconds' => $totalSeconds,
            'total_time' => $this->formatSeconds($totalSeconds),
        ];
    }

    private function groupTasksByField(array $tasks, string $fieldName, string $emptyLabel): array
    {
        $groups = [];

        foreach ($tasks as $task) {
            $label = trim((string)($task[$fieldName] ?? ''));

            if ($label === '') {
                $label = $emptyLabel;
            }

            if (!isset($groups[$label])) {
                $groups[$label] = [
                    'label' => $label,
                    'tasks_count' => 0,
                    'active_count' => 0,
                    'completed_count' => 0,
                    'total_seconds' => 0,
                    'total_time' => '00:00:00',
                ];
            }

            $groups[$label]['tasks_count']++;
            $groups[$label]['total_seconds'] += $this->getTaskActualSeconds($task);

            if (($task['status'] ?? '') === 'active') {
                $groups[$label]['active_count']++;
            }

            if (($task['status'] ?? '') === 'completed') {
                $groups[$label]['completed_count']++;
            }
        }

        foreach ($groups as &$group) {
            $group['total_time'] = $this->formatSeconds($group['total_seconds']);
        }

        usort($groups, function ($a, $b) {
            return $b['total_seconds'] <=> $a['total_seconds'];
        });

        return $groups;
    }

    private function getTaskActualSeconds(array $task): int
    {
        $seconds = (int)($task['accumulated_time'] ?? 0);

        if (($task['status'] ?? '') === 'active') {
            $lastStartedAt = (int)($task['last_started_at'] ?? 0);

            if ($lastStartedAt > 0) {
                $seconds += time() - $lastStartedAt;
            }
        }

        return max(0, $seconds);
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
private function readTimelineEvents(string $filePath, array $tasks): array
{
    if (!file_exists($filePath)) {
        return $this->buildFallbackTimelineEvents($tasks);
    }

    $xml = @simplexml_load_file($filePath);

    if (!$xml) {
        return $this->buildFallbackTimelineEvents($tasks);
    }

    $taskNames = $this->mapTaskNames($tasks);
    $events = [];

    foreach ($xml->action as $actionNode) {
        $createdAt = (string)($actionNode['created_at'] ?? '');

        if (empty($createdAt)) {
            continue;
        }

        $timestamp = strtotime($createdAt);

        if (!$timestamp) {
            continue;
        }

        $type = trim((string)$actionNode->type);
        $taskId = trim((string)$actionNode->task_id);

        if ($taskId === '') {
            continue;
        }

        $userName = trim((string)$actionNode->user_name);
        $description = trim((string)$actionNode->description);

        $taskName = $taskNames[(int)$taskId] ?? ('Задача #' . $taskId);

        $events[] = [
            'type' => $type ?: 'action',
            'task_id' => $taskId,
            'task_name' => $taskName,
            'user_name' => $userName ?: 'Невідомий користувач',
            'description' => $description ?: 'Дія із задачею',
            'created_at' => date('Y-m-d H:i:s', $timestamp),
            'timestamp' => $timestamp,
            'hour' => (int)date('H', $timestamp),
        ];
    }

    usort($events, function ($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });

    if (empty($events)) {
        return $this->buildFallbackTimelineEvents($tasks);
    }

    return $events;
}

    private function getXmlValue(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key])) {
                    return '';
                }

                return trim((string)$data[$key]);
            }
        }

        return '';
    }

    private function extractDateFromXmlNode(string $xml): string
    {
        if (preg_match('/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/', $xml, $matches)) {
            return $matches[0];
        }

        return '';
    }

    private function mapTaskNames(array $tasks): array
    {
        $map = [];

        foreach ($tasks as $task) {
            $id = (int)($task['id'] ?? 0);

            if ($id > 0) {
                $map[$id] = $task['name'] ?? ('Задача #' . $id);
            }
        }

        return $map;
    }

    private function buildFallbackTimelineEvents(array $tasks): array
    {
        $events = [];

        foreach ($tasks as $task) {
            if (($task['status'] ?? '') !== 'active') {
                continue;
            }

            $lastStartedAt = (int)($task['last_started_at'] ?? 0);

            if ($lastStartedAt <= 0) {
                continue;
            }

            $events[] = [
                'type' => 'play',
                'task_id' => $task['id'] ?? '',
                'task_name' => $task['name'] ?? 'Задача',
                'user_name' => $task['assigned_user_name'] ?? 'Невідомий користувач',
                'description' => 'Активна задача була запущена',
                'created_at' => date('Y-m-d H:i:s', $lastStartedAt),
                'timestamp' => $lastStartedAt,
                'hour' => (int)date('H', $lastStartedAt),
            ];
        }

        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $events;
    }

    private function buildTimelineStats(array $events): array
    {
        $hours = [];

        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = [
                'hour' => $i,
                'label' => sprintf('%02d:00', $i),
                'count' => 0,
            ];
        }

        foreach ($events as $event) {
            $hour = (int)($event['hour'] ?? 0);

            if (isset($hours[$hour])) {
                $hours[$hour]['count']++;
            }
        }

        $max = 0;

        foreach ($hours as $item) {
            if ($item['count'] > $max) {
                $max = $item['count'];
            }
        }

        return [
            'hours' => array_values($hours),
            'max' => max(1, $max),
        ];
    }

private function buildWorkTimeline(array $events, string $selectedDate): array
{
    $dayStart = strtotime($selectedDate . ' 00:00:00');
    $dayEnd = strtotime($selectedDate . ' 23:59:59');

    $openIntervals = [];
    $rows = [];

    foreach ($events as $event) {
        $type = $this->normalizeTimelineEventType(
            $event['type'] ?? '',
            $event['description'] ?? ''
        );

        $taskId = trim((string)($event['task_id'] ?? ''));

        if ($taskId === '') {
            continue;
        }

        if ($type === 'play') {
            $openIntervals[$taskId] = $event;
            continue;
        }

        if (($type === 'pause' || $type === 'complete') && isset($openIntervals[$taskId])) {
            $startEvent = $openIntervals[$taskId];
            unset($openIntervals[$taskId]);

            $start = (int)$startEvent['timestamp'];
            $end = (int)$event['timestamp'];

            if ($end <= $start) {
                continue;
            }

            if ($end < $dayStart || $start > $dayEnd) {
                continue;
            }

            $visibleStart = max($start, $dayStart);
            $visibleEnd = min($end, $dayEnd);

            $userName = trim((string)($startEvent['user_name'] ?? ''));

            if ($userName === '') {
                $userName = trim((string)($event['user_name'] ?? 'Невідомий користувач'));
            }

            $this->addTimelineInterval(
                $rows,
                $userName,
                $startEvent['task_name'] ?? $event['task_name'] ?? 'Задача',
                $visibleStart,
                $visibleEnd,
                $type
            );
        }
    }

    foreach ($openIntervals as $event) {
        $start = (int)$event['timestamp'];
        $end = time();

        if ($end <= $start) {
            continue;
        }

        if ($end < $dayStart || $start > $dayEnd) {
            continue;
        }

        $visibleStart = max($start, $dayStart);
        $visibleEnd = min($end, $dayEnd);

        $this->addTimelineInterval(
            $rows,
            $event['user_name'] ?? 'Невідомий користувач',
            $event['task_name'] ?? 'Задача',
            $visibleStart,
            $visibleEnd,
            'active'
        );
    }

    $rangeStart = strtotime($selectedDate . ' 00:00:00');
    $rangeEnd = strtotime($selectedDate . ' 23:59:59');

    foreach ($rows as &$row) {
        foreach ($row['intervals'] as &$interval) {
            $leftPercent = (($interval['start'] - $rangeStart) / ($rangeEnd - $rangeStart)) * 100;
            $widthPercent = (($interval['end'] - $interval['start']) / ($rangeEnd - $rangeStart)) * 100;

            $interval['left'] = max(0, round($leftPercent, 3));
            $interval['width'] = max(1.5, round($widthPercent, 3));
        }

        $row['lanes'] = $this->splitIntervalsIntoLanes($row['intervals']);
    }

    uasort($rows, function ($a, $b) {
        return $a['user_name'] <=> $b['user_name'];
    });

    return [
        'rows' => array_values($rows),
        'range_start' => $rangeStart,
        'range_end' => $rangeEnd,
        'hours' => $this->buildTimelineHourScale($rangeStart, $rangeEnd),
    ];
}

    private function addTimelineInterval(
        array &$rows,
        string $userName,
        string $taskName,
        int $start,
        int $end,
        string $finishType
    ): void {
        if (!isset($rows[$userName])) {
            $rows[$userName] = [
                'user_name' => $userName,
                'intervals' => [],
            ];
        }

        $rows[$userName]['intervals'][] = [
            'task_name' => $taskName,
            'start' => $start,
            'end' => $end,
            'start_label' => date('H:i', $start),
            'end_label' => date('H:i', $end),
            'duration' => $this->formatSeconds($end - $start),
            'left' => 0,
            'width' => 0,
            'finish_type' => $finishType,
        ];
    }

private function normalizeTimelineEventType(string $type, string $description): string
{
    $source = mb_strtolower($type . ' ' . $description);

    if (
        str_contains($source, 'play') ||
        str_contains($source, 'start') ||
        str_contains($source, 'active') ||
        str_contains($source, 'запуск') ||
        str_contains($source, 'запущ') ||
        str_contains($source, 'старт') ||
        str_contains($source, 'трекінгу')
    ) {
        return 'play';
    }

    if (
        str_contains($source, 'pause') ||
        str_contains($source, 'paused') ||
        str_contains($source, 'stop') ||
        str_contains($source, 'stopped') ||
        str_contains($source, 'зупинка') ||
        str_contains($source, 'зупин') ||
        str_contains($source, 'пауза') ||
        str_contains($source, 'пауз')
    ) {
        return 'pause';
    }

    if (
        str_contains($source, 'complete') ||
        str_contains($source, 'completed') ||
        str_contains($source, 'done') ||
        str_contains($source, 'finish') ||
        str_contains($source, 'finished') ||
        str_contains($source, 'заверш') ||
        str_contains($source, 'виконан') ||
        str_contains($source, 'маркування')
    ) {
        return 'complete';
    }

    return $type;
}

    private function buildTimelineHourScale(int $rangeStart, int $rangeEnd): array
    {
        $hours = [];
        $current = strtotime(date('Y-m-d H:00:00', $rangeStart));

        while ($current <= $rangeEnd) {
            $left = (($current - $rangeStart) / ($rangeEnd - $rangeStart)) * 100;

            $hours[] = [
                'label' => date('H:i', $current),
                'left' => round($left, 3),
            ];

            $current += 3600;
        }

        return $hours;
    }

    private function splitIntervalsIntoLanes(array $intervals): array
{
    usort($intervals, function ($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    $lanes = [];

    foreach ($intervals as $interval) {
        $placed = false;

        foreach ($lanes as &$lane) {
            $lastInterval = end($lane);

            if (!$lastInterval || $interval['start'] >= $lastInterval['end']) {
                $lane[] = $interval;
                $placed = true;
                break;
            }
        }

        if (!$placed) {
            $lanes[] = [$interval];
        }
    }

    return $lanes;
}

private function filterTimelineEventsByTasks(array $events, array $tasks): array
{
    $allowedTaskIds = [];

    foreach ($tasks as $task) {
        if (isset($task['id'])) {
            $allowedTaskIds[(string)$task['id']] = true;
        }
    }

    if (empty($allowedTaskIds)) {
        return [];
    }

    return array_values(array_filter($events, function ($event) use ($allowedTaskIds) {
        $taskId = (string)($event['task_id'] ?? '');

        return $taskId !== '' && isset($allowedTaskIds[$taskId]);
    }));
}
}