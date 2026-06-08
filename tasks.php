<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: tasks.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/TaskRepository.php";
require_once "classes/TaskActionDomLogger.php";
require_once "classes/MigrationService.php";
require_once "classes/ClientRepository.php";
require_once "classes/ProjectRepository.php";
require_once "classes/UserRepository.php";
require_once "classes/AuthService.php";

use App\Services\AuthService;
use App\Database\Database;
use App\Repositories\TaskRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Services\TaskActionDomLogger;
use App\Services\MigrationService;


$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$taskRepository = new TaskRepository($dbConnection);
$clientRepository = new ClientRepository($dbConnection);
$projectRepository = new ProjectRepository($dbConnection);
$userRepository = new UserRepository($dbConnection);

$taskActionDomLogger = new TaskActionDomLogger(__DIR__ . '/data/task-actions.xml');

if (!isset($_SESSION['user_logged_in_trigger'])) {
    $fakeLoginTime = date('Y-m-d H:i:s', time() - 720);
    $logLine = "[" . $fakeLoginTime . "] Авторизація користувача в системі ProHour\n";
    file_put_contents('log.txt', $logLine, FILE_APPEND);
    $_SESSION['user_logged_in_trigger'] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? '';
    $postExecuted = false;

    if ($action === 'create') {
        $taskName = trim($_POST['task_name'] ?? '');
        $clientId = (int)($_POST['client_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $newProjectName = trim($_POST['new_project_name'] ?? '');
        $assignedToUserId = (int)($_POST['assigned_to_user_id'] ?? $_SESSION['user_id']);
        $priority = trim($_POST['priority'] ?? 'normal');
        $deadline = trim($_POST['deadline'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (!empty($taskName)) {
            if ($projectId <= 0 && $clientId > 0 && !empty($newProjectName)) {
                $projectId = $projectRepository->getOrCreateProject($clientId, $newProjectName);
            }

            if ($projectId <= 0) {
                $projectId = null;
            }

            if ($assignedToUserId <= 0) {
                $assignedToUserId = (int)$_SESSION['user_id'];
            }

            $taskId = $taskRepository->createTask(
                (int)$_SESSION['user_id'],
                $assignedToUserId,
                $projectId,
                htmlspecialchars($taskName, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'),
                $priority,
                !empty($deadline) ? $deadline : null
            );

            $postExecuted = true;
        }
    }

    if ($taskId !== '') {
        switch ($action) {
            case 'play':
                $stmt = $dbConnection->prepare(
                    "UPDATE tasks SET status = 'active', last_started_at = :time WHERE id = :id"
                );

                $stmt->execute([
                    ':time' => time(),
                    ':id' => $taskId
                ]);

                $postExecuted = true;
                break;

            case 'pause':
                $stmt = $dbConnection->prepare("SELECT * FROM tasks WHERE id = :id");
                $stmt->execute([':id' => $taskId]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($task && $task['status'] === 'active') {
                    $newTime = $task['accumulated_time'] + (time() - $task['last_started_at']);

                    $update = $dbConnection->prepare(
                        "UPDATE tasks SET status = 'paused', accumulated_time = :acc, last_started_at = 0 WHERE id = :id"
                    );

                    $update->execute([
                        ':acc' => $newTime,
                        ':id' => $taskId
                    ]);
                }

                $postExecuted = true;
                break;

            case 'complete':
                $stmt = $dbConnection->prepare("SELECT * FROM tasks WHERE id = :id");
                $stmt->execute([':id' => $taskId]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($task) {
                    $newTime = $task['accumulated_time'];

                    if ($task['status'] === 'active') {
                        $newTime += time() - $task['last_started_at'];
                    }

                    $update = $dbConnection->prepare(
                        "UPDATE tasks SET status = 'completed', accumulated_time = :acc, last_started_at = 0 WHERE id = :id"
                    );

                    $update->execute([
                        ':acc' => $newTime,
                        ':id' => $taskId
                    ]);
                }

                $postExecuted = true;
                break;

            case 'delete':
                $stmt = $dbConnection->prepare("DELETE FROM tasks WHERE id = :id");
                $stmt->execute([':id' => $taskId]);
                $postExecuted = true;
                break;

            case 'edit':
                if (!empty($_POST['new_name'])) {
                    $stmt = $dbConnection->prepare(
                        "UPDATE tasks SET name = :name WHERE id = :id"
                    );

                    $stmt->execute([
                        ':name' => htmlspecialchars($_POST['new_name'], ENT_QUOTES, 'UTF-8'),
                        ':id' => $taskId
                    ]);
                }

                $postExecuted = true;
                break;
        }
    }

    if ($postExecuted) {
        $actionLabels = [
            'create' => 'Створення нового завдання',
            'play' => 'Запуск таймера трекінгу',
            'pause' => 'Зупинка таймера (Пауза)',
            'complete' => 'Маркування завдання як виконане',
            'delete' => 'Видалення завдання з бази даних',
            'edit' => 'Зміна назви завдання користувачем'
        ];

        $currentActionText = $actionLabels[$action] ?? 'Операція із завданням';

        $logLine = "[" . date('Y-m-d H:i:s') . "] " . $currentActionText . "\n";
        file_put_contents('log.txt', $logLine, FILE_APPEND);

        $taskActionDomLogger->log([
            'type' => $action,
            'description' => $currentActionText,
            'user_id' => $_SESSION['user_id'] ?? '',
            'user_name' => $_SESSION['user_name'] ?? '',
            'user_email' => $_SESSION['user_email'] ?? '',
            'task_id' => $taskId ?: '',
            'scheduler_task' => '',
        ]);

        $_SESSION['pending_notification'] = [
            'action' => $action,
            'description' => $currentActionText,
            'task_id' => $taskId ?: '',
        ];
    }

    header("Location: tasks.php");
    exit;
}

$tasks = $taskRepository->getTasksForUser(
    AuthService::getCurrentUserId(),
    AuthService::canSeeAllTeamData()
);
$clients = $clientRepository->getClients();
$projects = $projectRepository->getProjects();
$users = $userRepository->getUsers();

require_once "classes/TasksPage.php";

$page = new TasksPage(
    $translator->get('tasks_title'),
    $translator,
    $tasks,
    $clients,
    $projects,
    $users
);

$page->render();