<?php

session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: tasks.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/TaskRepository.php";
require_once "classes/TasksPage.php";

$translator = new Translator($currentLang);

$db = new Database();
$db->connect();
$db->createTables();

$taskRepository = new TaskRepository(
    $db->getConnection()
);

// Обробка POST-запитів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? '';

    switch ($action) {

        case 'create':

            if (!empty($_POST['task_name'])) {

                $taskName = htmlspecialchars(
                    trim($_POST['task_name'])
                );

                if ($taskName !== '') {
                    $taskRepository->createTask($taskName);
                }
            }

            break;

        case 'play':

            if ($taskId !== '') {

                $task = $taskRepository->getTaskById($taskId);

                if (
                    $task
                    &&
                    $task['status'] !== 'active'
                ) {
                    $taskRepository->playTask($taskId);
                }
            }

            break;

        case 'pause':

            if ($taskId !== '') {

                $task = $taskRepository->getTaskById($taskId);

                if (
                    $task
                    &&
                    $task['status'] === 'active'
                ) {
                    $taskRepository->pauseTask($taskId);
                }
            }

            break;

        case 'complete':

            if ($taskId !== '') {

                $task = $taskRepository->getTaskById($taskId);

                if (
                    $task
                    &&
                    $task['status'] !== 'completed'
                ) {
                    $taskRepository->completeTask($taskId);
                }
            }

            break;

        case 'delete':

            if ($taskId !== '') {

                $task = $taskRepository->getTaskById($taskId);

                if ($task) {
                    $taskRepository->deleteTask($taskId);
                }
            }

            break;

        case 'edit':

            if (
                $taskId !== ''
                &&
                !empty($_POST['new_name'])
            ) {

                $task = $taskRepository->getTaskById($taskId);

                $newName = htmlspecialchars(
                    trim($_POST['new_name'])
                );

                if (
                    $task
                    &&
                    $newName !== ''
                ) {
                    $taskRepository->editTask(
                        $taskId,
                        $newName
                    );
                }
            }

            break;
    }

    header("Location: tasks.php");
    exit;
}

$tasks = $taskRepository->getTasks();

$page = new TasksPage(
    $translator->get('tasks_title'),
    $translator,
    $tasks
);

$page->render();