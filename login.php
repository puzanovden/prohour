<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: login.php");
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
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Заповніть email та пароль.';
    } else {
        $loggedIn = $authService->login($email, $password);

        if ($loggedIn) {
            header("Location: tasks.php");
            exit;
        }

        $error = 'Невірний email або пароль.';
    }
}

$translator = new Translator($currentLang);
$title = 'Вхід | ProHour';

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
            <a href="register.php" class="user-status">Реєстрація</a>
            <div class="user-avatar">?</div>
        </div>
    </header>

    <main class="auth-page">
        <section class="auth-card reveal active">
            <h1>Вхід у ProHour</h1>
            <p>Увійдіть, щоб перейти до задач і трекінгу часу.</p>

            {$errorHtml}

            <form method="POST" class="auth-form">
                <label>Email</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>

                <label>Пароль</label>
                <input type="password" name="password" placeholder="Ваш пароль" required>

                <button type="submit" class="primary-btn">Увійти</button>
            </form>

            <div class="auth-bottom">
                Немає акаунта?
                <a href="register.php">Зареєструватися</a>
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