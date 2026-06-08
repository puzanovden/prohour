<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: profile.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";
require_once "classes/Page.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;

AuthService::requireAuth();

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$authService = new AuthService($dbConnection);
$translator = new Translator($currentLang);

$user = $authService->getCurrentUser();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_name') {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $error = 'Ім’я не може бути порожнім.';
        } elseif (mb_strlen($name) < 2) {
            $error = 'Ім’я має містити мінімум 2 символи.';
        } else {
            $updated = $authService->updateCurrentUserName($name);

            if ($updated) {
                $message = 'Профіль успішно оновлено.';
                $user = $authService->getCurrentUser();
            } else {
                $error = 'Не вдалося оновити профіль.';
            }
        }
    }

    if ($action === 'update_password') {
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $newPasswordConfirm = trim($_POST['new_password_confirm'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
            $error = 'Заповніть усі поля для зміни пароля.';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $error = 'Новий пароль і підтвердження не співпадають.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Новий пароль має містити мінімум 6 символів.';
        } else {
            $email = $user['email'] ?? '';
            $currentPasswordValid = $authService->login($email, $currentPassword);

            if (!$currentPasswordValid) {
                $error = 'Поточний пароль введено неправильно.';
            } else {
                $updated = $authService->updateCurrentUserPassword($newPassword);

                if ($updated) {
                    $message = 'Пароль успішно змінено.';
                    $user = $authService->getCurrentUser();
                } else {
                    $error = 'Не вдалося змінити пароль.';
                }
            }
        }
    }

    if ($action === 'update_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Оберіть файл аватарки.';
        } else {
            $allowedTypes = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp'
            ];

            $imageInfo = getimagesize($_FILES['avatar']['tmp_name']);

            if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
                $error = 'Дозволені тільки JPG, PNG або WEBP.';
            } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $error = 'Розмір аватарки не має перевищувати 2 МБ.';
            } else {
                $extension = $allowedTypes[$imageInfo[2]];
                $fileName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                $uploadDir = __DIR__ . '/uploads/avatars/';
                $relativePath = 'uploads/avatars/' . $fileName;

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $uploaded = move_uploaded_file(
                    $_FILES['avatar']['tmp_name'],
                    $uploadDir . $fileName
                );

                if ($uploaded) {
                    $updated = $authService->updateCurrentUserAvatar($relativePath);

                    if ($updated) {
                        $message = 'Аватарку успішно оновлено.';
                        $user = $authService->getCurrentUser();
                    } else {
                        $error = 'Не вдалося зберегти аватарку.';
                    }
                } else {
                    $error = 'Не вдалося завантажити файл.';
                }
            }
        }
    }
}

$title = 'Профіль | ProHour';

$nameValue = htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8');
$emailValue = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$roleValue = htmlspecialchars($user['role'] ?? 'user', ENT_QUOTES, 'UTF-8');
$avatarValue = htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8');

if (empty($avatarValue)) {
    $firstLetter = mb_substr($user['name'] ?? '?', 0, 1);
    $safeFirstLetter = htmlspecialchars($firstLetter, ENT_QUOTES, 'UTF-8');
    $avatarHtml = "<div class=\"profile-avatar-placeholder\">{$safeFirstLetter}</div>";
} else {
    $avatarHtml = "<img src=\"{$avatarValue}\" alt=\"Аватар користувача\" class=\"profile-avatar-img\">";
}

$messageHtml = '';
$errorHtml = '';

if (!empty($message)) {
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $messageHtml = "<div class=\"auth-success\">{$safeMessage}</div>";
}

if (!empty($error)) {
    $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    $errorHtml = "<div class=\"auth-error\">{$safeError}</div>";
}

class ProfilePage extends Page
{
    private string $nameValue;
    private string $emailValue;
    private string $roleValue;
    private string $avatarHtml;
    private string $messageHtml;
    private string $errorHtml;

    public function __construct(
        string $title,
        Translator $t,
        string $nameValue,
        string $emailValue,
        string $roleValue,
        string $avatarHtml,
        string $messageHtml,
        string $errorHtml
    ) {
        parent::__construct($title, $t);

        $this->nameValue = $nameValue;
        $this->emailValue = $emailValue;
        $this->roleValue = $roleValue;
        $this->avatarHtml = $avatarHtml;
        $this->messageHtml = $messageHtml;
        $this->errorHtml = $errorHtml;
    }

public function renderBody()
    {
        echo '<link rel="stylesheet" href="css/profile.css">';

        echo <<<HTML
        <main class="mvc-page profile-page">
            <section class="mvc-shell profile-shell">
                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">User profile</div>
                        <h1>Профіль користувача</h1>
                        <p>Керуйте персональними даними, аватаркою та параметрами доступу до ProHour.</p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>Роль користувача</span>
                        <strong>{$this->roleValue}</strong>
                    </div>
                </div>

                {$this->messageHtml}
                {$this->errorHtml}

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Фото профілю</h2>
                            <p>Натисніть на аватарку, щоб обрати нове зображення.</p>
                        </div>
                    </div>

                    <div class="profile-avatar-box">
                        <form method="POST" class="profile-avatar-form" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_avatar">

                            <label for="avatar" class="profile-avatar-click" title="Натисніть, щоб змінити аватарку">
                                {$this->avatarHtml}
                                <span class="profile-avatar-overlay">Змінити фото</span>
                            </label>

                            <input 
                                type="file" 
                                id="avatar" 
                                name="avatar" 
                                class="profile-avatar-input"
                                accept="image/png, image/jpeg, image/webp"
                                onchange="this.form.submit()"
                            >
                        </form>

                        <div class="profile-avatar-text">
                            <strong>{$this->nameValue}</strong>
                            <span>{$this->emailValue}</span>
                        </div>
                    </div>
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Особисті дані</h2>
                            <p>Оновіть ім’я, яке відображається в системі.</p>
                        </div>
                    </div>

                    <div class="profile-info">
                        <div>
                            <span>Email</span>
                            <strong>{$this->emailValue}</strong>
                        </div>
                        <div>
                            <span>Роль</span>
                            <strong>{$this->roleValue}</strong>
                        </div>
                    </div>

                    <form method="POST" class="mvc-form profile-form" autocomplete="on">
                        <input type="hidden" name="action" value="update_name">

                        <label>
                            Ім’я
                            <input 
                                type="text"
                                id="name"
                                name="name"
                                value="{$this->nameValue}"
                                autocomplete="name"
                                minlength="2"
                                required
                            >
                        </label>

                        <button type="submit">Зберегти профіль</button>
                    </form>
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Зміна пароля</h2>
                            <p>Вкажіть поточний пароль і нове значення для оновлення доступу.</p>
                        </div>
                    </div>

                    <form method="POST" class="mvc-form profile-password-form" autocomplete="on">
                        <input type="hidden" name="action" value="update_password">

                        <label>
                            Поточний пароль
                            <input 
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                        </label>

                        <label>
                            Новий пароль
                            <input 
                                type="password"
                                id="new_password"
                                name="new_password"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </label>

                        <label>
                            Підтвердження нового пароля
                            <input 
                                type="password"
                                id="new_password_confirm"
                                name="new_password_confirm"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </label>

                        <button type="submit">Змінити пароль</button>
                    </form>
                </section>
            </section>
        </main>
    HTML;
    }
}

$page = new ProfilePage(
    $title,
    $translator,
    $nameValue,
    $emailValue,
    $roleValue,
    $avatarHtml,
    $messageHtml,
    $errorHtml
);

$page->render();