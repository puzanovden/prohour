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

use App\Database\Database;
use App\Services\AuthService;

$db = new Database();
$authService = new AuthService($db->getConnection());

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Заповніть усі поля.';
    } else {
        $registered = $authService->register($email, $password, $name);

        if ($registered) {
            header("Location: tasks.php");
            exit;
        }

        $error = 'Користувач із таким email вже існує.';
    }
}

$translator = new Translator($currentLang);
$title = 'Реєстрація | ProHour';

$activeUa = $translator->getLang() === 'uk' ? 'active' : '';
$activeEn = $translator->getLang() === 'en' ? 'active' : '';

$errorHtml = '';

if (!empty($error)) {
    $errorHtml = "<div class=\"auth-error\">$error</div>";
}

echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css"> 
</head>
<body id="for-whom">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header>
        <a href="index.php" class="logo">
            <img src="img/logo.png" alt="ProHour">
        </a>

        <nav>
            <a href="index.php#for-whom">{$translator->get('nav_for_whom')}</a>
            <a href="index.php#features">{$translator->get('nav_features')}</a>
            <a href="index.php#workflow">{$translator->get('nav_how')}</a>
            <a href="index.php#analytics">{$translator->get('nav_analytics')}</a>
        </nav>

        <div class="lang-switch">
            <a href="?lang=uk" class="lang-btn {$activeUa}">
                <img src="img/ua.svg" alt="UA"> UA
            </a>
            <a href="?lang=en" class="lang-btn {$activeEn}">
                <img src="img/gb.svg" alt="EN"> EN
            </a>
        </div>

        <div class="user-panel">
            <a href="login.php" class="user-status">Увійти</a>
            <div class="user-avatar">?</div>
        </div>
    </header>

    <main class="auth-page">
        <section class="auth-card reveal active">
            <h1>Реєстрація в ProHour</h1>
            <p>Створіть акаунт, щоб користуватися трекером задач.</p>

            {$errorHtml}

            <form method="POST" class="auth-form">
                <label>Ім’я</label>
                <input type="text" name="name" placeholder="Ваше ім’я" required>

                <label>Email</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>

                <label>Пароль</label>
                <input type="password" name="password" placeholder="Ваш пароль" required>

                <button type="submit" class="primary-btn">Зареєструватися</button>
            </form>

            <div class="auth-bottom">
                Уже є акаунт?
                <a href="login.php">Увійти</a>
            </div>
        </section>
    </main>

    <footer>
        <div>ProHour © 2026</div>
        <div>Smart Time Tracking System</div>
    </footer>
</body>
</html>
HTML;