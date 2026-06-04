<?php

namespace App\Controllers;

use App\Adapters\DatabaseAdapterInterface;
use App\Core\View;
use App\Factories\ModelFactory;
use App\Repositories\LoggingTaskWriterDecorator;
use App\Repositories\TaskWriterRepository;

class TaskMvcController
{
    private DatabaseAdapterInterface $databaseAdapter;

    public function __construct(DatabaseAdapterInterface $databaseAdapter)
    {
        $this->databaseAdapter = $databaseAdapter;
    }

    public function createForm(): void
    {
        View::render('tasks/create', [
            'title' => 'Створення задачі через MVC',
            'success' => isset($_GET['created']),
        ]);
    }

    public function store(): void
    {
        $db = $this->databaseAdapter->connect();

        $task = ModelFactory::createTask([
            'user_id' => $_SESSION['user_id'] ?? null,
            'project_id' => null,
            'name' => $_POST['name'] ?? '',
            'status' => 'paused',
            'comment' => $_POST['comment'] ?? null,
            'accumulated_time' => 0,
            'last_started_at' => 0,
        ]);

        if ($task->name === '') {
            header('Location: app.php?route=tasks/create');
            exit;
        }

        $writer = new LoggingTaskWriterDecorator(
            new TaskWriterRepository($db),
            __DIR__ . '/../../logs/model-save.log'
        );

        $writer->save($task);

        header('Location: app.php?route=tasks/create&created=1');
        exit;
    }
}