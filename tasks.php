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
require_once "classes/RegexHelper.php";

use App\Database\Database;
use App\Repositories\TaskRepository;
use App\Utils\RegexHelper;

$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();
$taskRepository = new TaskRepository($dbConnection);

if (!file_exists('scheduler.txt')) {
    file_put_contents('scheduler.txt', "AutoBackupTask=02:00\nSendEmailReports=08:30\nSystemCleanUpTask=00:00");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? '';
    $cronTaskName = $_POST['cron_task_name'] ?? '';

    // Керування планувальником завдань через Regex
    if ($action === 'scheduler_toggle') {
        $isActive = $_POST['cron_is_active'] === '1';
        if ($isActive) {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'disable');
        } else {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'schedule', '12:00');
        }
        header("Location: tasks.php#scheduler");
        exit;
    }

    if ($action === 'scheduler_schedule') {
        $cronTime = $_POST['cron_task_time'] ?? '12:00';
        RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'schedule', $cronTime);
        header("Location: tasks.php#scheduler");
        exit;
    }

    if ($action === 'scheduler_rename') {
        $newCronName = trim($_POST['new_cron_name'] ?? '');
        $newCronName = preg_replace('/[^a-zA-Z0-9_]/', '', $newCronName); // Очищення імені через Regex
        
        if (!empty($newCronName)) {
            RegexHelper::manageTaskScheduler('scheduler.txt', $cronTaskName, 'rename', $newCronName);
        }
        header("Location: tasks.php#scheduler");
        exit;
    }

    // Операції над тасками користувача в БД через точні Prepared Statements
    if ($action === 'create' && !empty($_POST['task_name'])) {
        $stmt = $dbConnection->prepare("INSERT INTO tasks (name, status, accumulated_time, last_started_at) VALUES (:name, 'paused', 0, 0)");
        $stmt->execute([':name' => htmlspecialchars($_POST['task_name'])]);
    }

    if ($taskId !== '') {
        switch ($action) {
            case 'play':
                $stmt = $dbConnection->prepare("UPDATE tasks SET status = 'active', last_started_at = :time WHERE id = :id");
                $stmt->execute([':time' => time(), ':id' => $taskId]);
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
                break;

            case 'delete':
                $stmt = $dbConnection->prepare("DELETE FROM tasks WHERE id = :id");
                $stmt->execute([':id' => $taskId]);
                break;
                
            case 'edit':
                if (!empty($_POST['new_name'])) {
                    $stmt = $dbConnection->prepare("UPDATE tasks SET name = :name WHERE id = :id");
                    $stmt->execute([':name' => htmlspecialchars($_POST['new_name']), ':id' => $taskId]);
                }
                break;
        }
    }
    
    header("Location: tasks.php");
    exit;
}

// Збір даних планувальника регулярними виразами
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

require_once "classes/TasksPage.php";
$page = new TasksPage($translator->get('tasks_title'), $translator, $tasks, $schedulerData);
$page->render();