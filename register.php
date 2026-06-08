<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: register.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$authService = new AuthService($dbConnection);
$translator = new Translator($currentLang);

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Заповніть усі поля.';
    } elseif (mb_strlen($name) < 2) {
        $error = 'Ім’я має містити мінімум 2 символи.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введіть коректний email.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль має містити мінімум 6 символів.';
    } else {
        $registered = $authService->register($email, $password, $name);

        if ($registered) {
            header("Location: tasks.php");
            exit;
        }

        $error = 'Користувач із таким email вже існує.';
    }
}

$title = 'Реєстрація | ProHour';

$activeUa = $translator->getLang() === 'uk' ? 'active' : '';
$activeEn = $translator->getLang() === 'en' ? 'active' : '';

$nameValue = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$emailValue = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

$errorHtml = '';

if (!empty($error)) {
    $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    $errorHtml = "<div class=\"auth-mvc-error\"><strong>{$safeError}</strong></div>";
}

echo <<<HTML
<!DOCTYPE html>
<html lang="{$currentLang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" href="css/mvc.css">
    <link rel="stylesheet" href="css/auth-mvc.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="mvc-body">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header class="mvc-header">
        <a href="index.php" class="mvc-logo">
            <img src="img/logo.png" alt="ProHour">
        </a>

        <nav class="mvc-nav">
            <a href="index.php#for-whom">{$translator->get('nav_for_whom')}</a>
            <a href="index.php#features">{$translator->get('nav_features')}</a>
            <a href="index.php#workflow">{$translator->get('nav_how')}</a>
            <a href="index.php#analytics">{$translator->get('nav_analytics')}</a>
        </nav>

        <div class="mvc-header-actions">
            <div class="lang-switch mvc-lang-switch">
                <a href="?lang=uk" class="lang-btn {$activeUa}">
                    <img src="img/ua.svg" alt="UA"> UA
                </a>
                <a href="?lang=en" class="lang-btn {$activeEn}">
                    <img src="img/gb.svg" alt="EN"> EN
                </a>
            </div>

            <div class="mvc-user-panel">
                <a href="login.php">Увійти</a>
            </div>
        </div>
    </header>

    <main class="mvc-page auth-mvc-page">
        <section class="mvc-shell auth-mvc-shell">
            <div class="auth-mvc-layout">
                <div class="auth-mvc-hero">
                    <div class="mvc-eyebrow">Create account</div>
                    <h1>Реєстрація в ProHour</h1>
                    <p>
                        Створіть акаунт, щоб працювати з задачами, клієнтами, проєктами та командною аналітикою.
                    </p>

                    <div class="auth-mvc-benefits">
                        <div>
                            <span>👥</span>
                            <strong>Команда</strong>
                            <p>Користувачі працюють у межах спільної команди.</p>
                        </div>

                        <div>
                            <span>✅</span>
                            <strong>Задачі</strong>
                            <p>Призначайте задачі собі або іншим користувачам.</p>
                        </div>

                        <div>
                            <span>🔐</span>
                            <strong>Безпека</strong>
                            <p>Доступ до робочого простору захищено авторизацією.</p>
                        </div>
                    </div>
                </div>

                <div class="auth-mvc-card">
                    <h2>Новий акаунт</h2>
                    <p>Заповніть дані для створення користувача.</p>

                    {$errorHtml}

                    <form method="POST" class="mvc-form auth-mvc-form" autocomplete="on">
                        <label>
                            Ім’я
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="Ваше ім’я" 
                                autocomplete="name"
                                value="{$nameValue}"
                                required
                            >
                        </label>

                        <label>
                            Email
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="example@gmail.com" 
                                autocomplete="email"
                                inputmode="email"
                                value="{$emailValue}"
                                required
                            >
                        </label>

                        <label>
                            Пароль
                            <input 
                                type="password" 
                                name="password" 
                                placeholder="Ваш пароль" 
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </label>

                        <button type="submit">Зареєструватися</button>
                    </form>

                    <div class="auth-mvc-bottom">
                        Уже є акаунт?
                        <a href="login.php">Увійти</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
HTML;