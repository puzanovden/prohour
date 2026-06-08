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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Заповніть email та пароль.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введіть коректний email.';
    } else {
        $loggedIn = $authService->login($email, $password);

        if ($loggedIn) {
            header("Location: tasks.php");
            exit;
        }

        $error = 'Невірний email або пароль.';
    }
}

$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : [];

$googleClientId = htmlspecialchars(
    $config['google_client_id'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$title = 'Вхід | ProHour';

$activeUa = $translator->getLang() === 'uk' ? 'active' : '';
$activeEn = $translator->getLang() === 'en' ? 'active' : '';

$emailValue = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

$errorHtml = '';

if (!empty($error)) {
    $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    $errorHtml = "<div class=\"auth-mvc-error\"><strong>{$safeError}</strong></div>";
}

$googleBlock = '';

if (!empty($googleClientId)) {
    $googleBlock = <<<HTML
    <div class="auth-google-divider">
        <span>або</span>
    </div>

    <div 
        id="g_id_onload"
        data-client_id="{$googleClientId}"
        data-callback="handleGoogleCredential"
        data-auto_prompt="false">
    </div>

    <div 
        class="g_id_signin"
        data-type="standard"
        data-size="large"
        data-theme="outline"
        data-text="signin_with"
        data-shape="pill"
        data-logo_alignment="left">
    </div>
HTML;
}

echo <<<HTML
<!DOCTYPE html>
<html lang="{$currentLang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/mvc.css">
    <link rel="stylesheet" href="css/auth-mvc.css">

    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
                <a href="login.php" class="active">Увійти</a>
                <a href="register.php" class="mvc-register-link">Реєстрація</a>
            </div>
        </div>
    </header>

    <main class="mvc-page auth-mvc-page">
        <section class="mvc-shell auth-mvc-shell">
            <div class="auth-mvc-layout">
                <div class="auth-mvc-hero">
                    <div class="mvc-eyebrow">Welcome back</div>

                    <h1>Вхід у ProHour</h1>

                    <p>
                        Увійдіть, щоб перейти до задач, клієнтів, проєктів, чату та аналітики робочого часу.
                    </p>

                    <div class="auth-mvc-benefits">
                        <div>
                            <span>⏱</span>
                            <strong>Трекінг часу</strong>
                            <p>Фіксуйте фактично витрачений час по задачах.</p>
                        </div>

                        <div>
                            <span>📁</span>
                            <strong>Проєкти</strong>
                            <p>Працюйте з клієнтами, проєктами та виконавцями.</p>
                        </div>

                        <div>
                            <span>📊</span>
                            <strong>Аналітика</strong>
                            <p>Контролюйте прогрес команди в одному просторі.</p>
                        </div>
                    </div>
                </div>

                <div class="auth-mvc-card">
                    <h2>Авторизація</h2>
                    <p>Введіть email і пароль для доступу до системи.</p>

                    {$errorHtml}

                    <form method="POST" class="mvc-form auth-mvc-form" autocomplete="on">
                        <label>
                            Email
                            <input 
                                type="email" 
                                id="email"
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
                                id="password"
                                name="password" 
                                placeholder="Ваш пароль" 
                                autocomplete="current-password"
                                required
                            >
                        </label>

                        <button type="submit">Увійти</button>
                    </form>

                    {$googleBlock}

                    <div class="auth-mvc-bottom">
                        Немає акаунта?
                        <a href="register.php">Зареєструватися</a>
                    </div>

                    <div class="auth-mvc-demo">
                        <strong>Демо-адмін:</strong>
                        <span>admin@prohour.local / admin</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function handleGoogleCredential(response) {
    console.log('Google response object:', response);
    console.log('Credential exists:', !!response.credential);
    console.log('Credential length:', response.credential ? response.credential.length : 0);
    console.log('Credential start:', response.credential ? response.credential.substring(0, 40) : null);

    const formData = new FormData();

    formData.append('credential', response.credential);

    fetch('google-login.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        console.log('google-login.php status:', response.status);
        console.log('google-login.php content-type:', response.headers.get('content-type'));

        const text = await response.text();

        console.log('google-login.php raw response:', text);

        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error:', error);
            console.error('Response was not valid JSON:', text);

            throw error;
        }
    })
    .then(data => {
        console.log('google-login.php parsed data:', data);

        if (data.success) {
            window.location.href = data.redirect || 'tasks.php';
            return;
        }

        alert(data.message || 'Не вдалося увійти через Google.');
    })
    .catch((error) => {
        console.error('Google auth fetch error:', error);
        alert('Помилка Google авторизації. Дивись Console.');
    });
}
    </script>
</body>
</html>
HTML;