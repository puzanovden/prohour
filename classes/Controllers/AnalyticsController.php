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

        $tasks = $taskRepository->getTasks();

        $strategy = $this->resolveStrategy($_GET['strategy'] ?? 'simple');
        $analytics = $strategy->calculate($tasks);

        View::render('analytics/index', [
            'title' => 'MVC-аналітика ProHour',
            'adapterName' => $this->databaseAdapter->getName(),
            'strategyName' => $strategy->getName(),
            'selectedStrategy' => $_GET['strategy'] ?? 'simple',
            'analytics' => $analytics,
            'tasks' => $tasks,
        ]);
    }

    private function resolveStrategy(string $strategyName): TaskTimeAnalyticsStrategyInterface
    {
        if ($strategyName === 'status') {
            return new StatusGroupedTaskTimeAnalyticsStrategy();
        }

        return new SimpleTaskTimeAnalyticsStrategy();
    }
}