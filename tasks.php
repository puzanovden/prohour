<?php
session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: tasks.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
$translator = new Translator($currentLang);


if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [];
}

// Обробка POST-запитів (зміна стану задач)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? '';

    if ($action === 'create' && !empty($_POST['task_name'])) {
        $newId = uniqid('task_');
        $_SESSION['tasks'][$newId] = [
            'id' => $newId,
            'name' => htmlspecialchars($_POST['task_name']),
            'status' => 'paused',
            'accumulated_time' => 0.0,
            'last_started_at' => null
        ];
    }

    // Обробка існуючих задач
    if ($taskId !== '' && isset($_SESSION['tasks'][$taskId])) {
        $task = &$_SESSION['tasks'][$taskId];

        switch ($action) {
            case 'play':
                if ($task['status'] !== 'active') {
                    $task['status'] = 'active';
                    $task['last_started_at'] = time();
                }
                break;

            case 'pause':
                if ($task['status'] === 'active') {
                    $task['status'] = 'paused';
                    $task['accumulated_time'] += (time() - $task['last_started_at']);
                    $task['last_started_at'] = null;
                }
                break;

            case 'complete':
                if ($task['status'] === 'active') {
                    $task['accumulated_time'] += (time() - $task['last_started_at']);
                }
                $task['status'] = 'completed';
                $task['last_started_at'] = null;
                break;

            case 'delete':
                unset($_SESSION['tasks'][$taskId]);
                break;
                
            case 'edit':
                if (!empty($_POST['new_name'])) {
                    $task['name'] = htmlspecialchars($_POST['new_name']);
                }
                break;
        }
    }
    
    // Перенаправлення на ту саму сторінку (щоб уникнути повторної відправки форми при F5)
    header("Location: tasks.php");
    exit;
}

require_once "classes/TasksPage.php";

$page = new TasksPage($translator->get('tasks_title'), $translator, $_SESSION['tasks']);
$page->render();