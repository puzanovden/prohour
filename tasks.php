<?php
session_start();
date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: tasks.php");
    exit;
}



$currentLang = $_SESSION['lang'] ?? 'uk';

//☺
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
//☺

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/TaskRepository.php";
require_once "classes/RegexHelper.php";
require_once "classes/TaskActionDomLogger.php";

use App\Database\Database;
use App\Repositories\TaskRepository;
use App\Utils\RegexHelper;
use App\Services\TaskActionDomLogger;

$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();
$taskRepository = new TaskRepository($dbConnection);
$taskActionDomLogger = new TaskActionDomLogger(__DIR__ . '/data/task-actions.xml');

if (!file_exists('scheduler.txt')) {
    file_put_contents('scheduler.txt', "AutoBackupTask=02:00\nSendEmailReports=08:30\nSystemCleanUpTask=00:00");
}

if (!isset($_SESSION['user_logged_in_trigger'])) {
    $fakeLoginTime = date('Y-m-d H:i:s', time() - 720); 
    $logLine = "[" . $fakeLoginTime . "] Авторизація користувача в системі ProHour\n";
    file_put_contents('log.txt', $logLine, FILE_APPEND);
    $_SESSION['user_logged_in_trigger'] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? '';
    $cronTaskName = $_POST['cron_task_name'] ?? '';

    if ($action === 'scheduler_toggle') {
        $isActive = $_POST['cron_is_active'] === '1';
        if ($isActive) {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'disable');
        } else {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'schedule', '12:00');
        }
        $postExecuted = true;
    }

    if ($action === 'scheduler_schedule') {
        $cronTime = $_POST['cron_task_time'] ?? '12:00';
        RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'schedule', $cronTime);
        $postExecuted = true;
    }

    if ($action === 'scheduler_rename') {
        $newCronName = trim($_POST['new_cron_name'] ?? '');
        $newCronName = preg_replace('/[^a-zA-Z0-9_]/', '', $newCronName);
        
        if (!empty($newCronName)) {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'rename', $newCronName);
        }
        $postExecuted = true;
    }

    if ($action === 'create' && !empty($_POST['task_name'])) {
        $stmt = $dbConnection->prepare(
            "INSERT INTO tasks (user_id, name, status, accumulated_time, last_started_at)
            VALUES (:user_id, :name, 'paused', 0, 0)"
        );

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':name' => htmlspecialchars($_POST['task_name'], ENT_QUOTES, 'UTF-8')
        ]);

        $taskId = $dbConnection->lastInsertId();
        $postExecuted = true;
    }

    if ($taskId !== '') {
        switch ($action) {
            case 'play':
                $stmt = $dbConnection->prepare("UPDATE tasks SET status = 'active', last_started_at = :time WHERE id = :id");
                $stmt->execute([':time' => time(), ':id' => $taskId]);
                $postExecuted = true;
                break;

            case 'pause':
                $stmt = $dbConnection->prepare("SELECT * FROM tasks WHERE id = :id");
                $stmt->execute([':id' => $taskId]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($task && $task['status'] === 'active') {
                    $newTime = $task['accumulated_time'] + (time() - $task['last_started_at']);
                    $update = $dbConnection->prepare("UPDATE tasks SET status = 'paused', accumulated_time = :acc, last_started_at = 0 WHERE id = :id");
                    $update->execute([':acc' => $newTime, ':id' => $taskId]);
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
                        $newTime += (time() - $task['last_started_at']);
                    }
                    $update = $dbConnection->prepare("UPDATE tasks SET status = 'completed', accumulated_time = :acc, last_started_at = 0 WHERE id = :id");
                    $update->execute([':acc' => $newTime, ':id' => $taskId]);
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
                    $stmt = $dbConnection->prepare("UPDATE tasks SET name = :name WHERE id = :id");
                    $stmt->execute([':name' => htmlspecialchars($_POST['new_name']), ':id' => $taskId]);
                }
                $postExecuted = true;
                break;
        }
    }
    
    if (isset($postExecuted) && $postExecuted === true) {
    $actionLabels = [
        'create' => 'Створення нового завдання',
        'play' => 'Запуск таймера трекінгу',
        'pause' => 'Зупинка таймера (Пауза)',
        'complete' => 'Маркування завдання як виконане',
        'delete' => 'Видалення завдання з бази даних',
        'edit' => 'Зміна назви завдання користувачем',
        'scheduler_toggle' => 'Перемикання активності фонової служби',
        'scheduler_schedule' => 'Оновлення часового тригера фонової служби',
        'scheduler_rename' => 'Зміна системного імені служби автоматизації'
    ];

    $currentActionText = $actionLabels[$action] ?? 'Системна операція';

    $logLine = "[" . date('Y-m-d H:i:s') . "] " . $currentActionText . "\n";
    file_put_contents('log.txt', $logLine, FILE_APPEND);

    $taskActionDomLogger->log([
        'type' => $action,
        'description' => $currentActionText,
        'user_id' => $_SESSION['user_id'] ?? '',
        'user_name' => $_SESSION['user_name'] ?? '',
        'user_email' => $_SESSION['user_email'] ?? '',
        'task_id' => $taskId ?: '',
        'scheduler_task' => $cronTaskName ?: '',
    ]);
}

    if (strpos($action, 'scheduler_') === 0) {
        header("Location: tasks.php#scheduler");
    } else {
        header("Location: tasks.php");
    }
    exit;
}

$schedulerData = [];
if (file_exists('scheduler.txt')) {
    $lines = file('scheduler.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (preg_match("/^#\s*([^=]+)=disabled/", $line, $matches)) {
            $schedulerData[trim($matches[1])] = ['active' => false, 'time' => 'Вимкнено'];
        } elseif (preg_match("/^([^=]+)=(.*)/", $line, $matches)) {
            $schedulerData[trim($matches[1])] = ['active' => true, 'time' => trim($matches[2])];
        }
    }
}

$tasks = $taskRepository->getTasks();
$logData = RegexHelper::analyzeLogFile('log.txt');

require_once "classes/TasksPage.php";
$page = new TasksPage($translator->get('tasks_title'), $translator, $tasks, $schedulerData, $logData);
$page->render();