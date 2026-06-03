<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: index.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/HomePage.php";
require_once "classes/Database.php";
require_once "classes/TaskRepository.php";
require_once "classes/RegexHelper.php";

// завдання 9
use App\Database\Database;
use App\Repositories\TaskRepository;
use App\Utils\RegexHelper;

$translator = new Translator($currentLang);

// завдання 1 & 5
$db = new Database();

$taskRepository = new TaskRepository($db->getConnection());
$tasks = $taskRepository->getTasks();

$labStatus = ['errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_email'])) {
    $email = trim($_POST['feedback_email']);
    $url = trim($_POST['feedback_project_url']);
    $deadline = trim($_POST['feedback_deadline']);
    $message = trim($_POST['feedback_message']);

    // e-mail
    if (!RegexHelper::validateEmail($email)) {
        $labStatus['errors'][] = "Синтаксична помилка в полі Email!";
    }

    // Блок 1, Завдання 1
    if (!empty($url) && !RegexHelper::validateUrl($url)) {
        $labStatus['errors'][] = "Дійсна URL-адреса репозиторію не розпізнана!";
    }

    // Блок 1, Завдання 2
    if (!empty($deadline) && !RegexHelper::validateDate($deadline)) {
        $labStatus['errors'][] = "Дата дедлайну повинна бути у форматі dd/mm/yyyy та в межах 1600-9999 років!";
    }

    if (empty($labStatus['errors'])) {
        $htmlFormattedMessage = RegexHelper::stringToHtml($message);

        // Блок 2, Завдання 2
        $logLine = "[" . date('Y-m-d H:i:s') . "] Відправлено фідбек від $email. Текст: " . str_replace("\n", " ", $message) . "\n";
        file_put_contents('log.txt', $logLine, FILE_APPEND);

        $parsedLog = RegexHelper::analyzeLogFile('log.txt');

        // Блок 2, Завдання 1 & Блок 3, Завдання 1
        RegexHelper::manageTaskScheduler('scheduler.txt', 'SendEmailNotification', 'schedule', '23:59');
        RegexHelper::manageTaskScheduler('scheduler.txt', 'OldCleanUpTask', 'disable');

        $taskRepository->batchInsertTasks([
            ['name' => 'Обробити вхідний запит клієнта (' . $email . ')', 'status' => 'paused']
        ]);

        $labStatus['success'] = true;
        $labStatus['last_time'] = $parsedLog['time'];
    }
}
$homePage = new HomePage($translator->get('home_title'), $translator, $labStatus);
$homePage->render();