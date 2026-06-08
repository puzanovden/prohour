<?php

session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];

    $table = $_GET['table'] ?? 'users';

    header("Location: admin.php?table=" . urlencode($table));
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";
require_once "classes/UserRepository.php";
require_once "classes/TeamRepository.php";
require_once "classes/AdminRepository.php";
require_once "classes/Page.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;
use App\Repositories\UserRepository;
use App\Repositories\TeamRepository;
use App\Repositories\AdminRepository;

AuthService::requireAdmin();

$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$userRepository = new UserRepository($dbConnection);
$teamRepository = new TeamRepository($dbConnection);
$adminRepository = new AdminRepository($dbConnection);

$allowedRoles = ['admin', 'manager', 'employee'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = trim($_POST['role'] ?? 'employee');
        $teamId = (int)($_POST['team_id'] ?? 1);

        if (!in_array($role, $allowedRoles, true)) {
            $role = 'employee';
        }

        if ($userId > 0) {
            $userRepository->updateUserRoleAndTeam($userId, $role, $teamId);

            if ($userId === (int)$_SESSION['user_id']) {
                $_SESSION['user_role'] = $role;
                $_SESSION['team_id'] = $teamId;
            }

            $_SESSION['flash_message'] = 'Дані користувача оновлено.';
        }

        header("Location: admin.php");
        exit;
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId > 0 && $userId !== (int)$_SESSION['user_id']) {
            $userRepository->deleteUserById($userId);
            $_SESSION['flash_message'] = 'Користувача видалено.';
        } else {
            $_SESSION['flash_error'] = 'Не можна видалити поточного адміністратора.';
        }

        header("Location: admin.php");
        exit;
    }

    if ($action === 'admin_create_row') {
        $table = trim($_POST['table'] ?? '');
        $data = $_POST['data'] ?? [];

        if ($adminRepository->createRow($table, $data)) {
            $_SESSION['flash_message'] = 'Запис додано.';
        } else {
            $_SESSION['flash_error'] = 'Не вдалося додати запис.';
        }

        header("Location: admin.php?table=" . urlencode($table));
        exit;
    }

    if ($action === 'admin_update_row') {
        $table = trim($_POST['table'] ?? '');
        $rowId = (int)($_POST['row_id'] ?? 0);
        $data = $_POST['data'] ?? [];

        if ($rowId > 0 && $adminRepository->updateRow($table, $rowId, $data)) {
            $_SESSION['flash_message'] = 'Запис оновлено.';
        } else {
            $_SESSION['flash_error'] = 'Не вдалося оновити запис.';
        }

        header("Location: admin.php?table=" . urlencode($table));
        exit;
    }

    if ($action === 'admin_delete_row') {
        $table = trim($_POST['table'] ?? '');
        $rowId = (int)($_POST['row_id'] ?? 0);

        if ($table === 'users' && $rowId === (int)$_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Не можна видалити поточного адміністратора.';
        } elseif ($rowId > 0 && $adminRepository->deleteRow($table, $rowId)) {
            $_SESSION['flash_message'] = 'Запис видалено.';
        } else {
            $_SESSION['flash_error'] = 'Не вдалося видалити запис.';
        }

        header("Location: admin.php?table=" . urlencode($table));
        exit;
    }
}

$users = $userRepository->getUsers();
$teams = $teamRepository->getTeams();

$selectedTable = trim($_GET['table'] ?? 'users');

if (!$adminRepository->isAllowedTable($selectedTable)) {
    $selectedTable = 'users';
}

$allowedTables = $adminRepository->getAllowedTables();
$tableColumns = $adminRepository->getTableColumns($selectedTable);
$tableEditableColumns = $adminRepository->getEditableColumns($selectedTable);
$tableRows = $adminRepository->getRows($selectedTable);

$message = $_SESSION['flash_message'] ?? '';
$error = $_SESSION['flash_error'] ?? '';

unset($_SESSION['flash_message']);
unset($_SESSION['flash_error']);

class AdminPage extends Page
{
    private array $users;
    private array $teams;
    private array $allowedTables;
    private string $selectedTable;
    private array $tableColumns;
    private array $tableEditableColumns;
    private array $tableRows;
    private string $message;
    private string $error;

    public function __construct(
        string $title,
        Translator $t,
        array $users,
        array $teams,
        array $allowedTables,
        string $selectedTable,
        array $tableColumns,
        array $tableEditableColumns,
        array $tableRows,
        string $message = '',
        string $error = ''
    ) {
        parent::__construct($title, $t);

        $this->users = $users;
        $this->teams = $teams;
        $this->allowedTables = $allowedTables;
        $this->selectedTable = $selectedTable;
        $this->tableColumns = $tableColumns;
        $this->tableEditableColumns = $tableEditableColumns;
        $this->tableRows = $tableRows;
        $this->message = $message;
        $this->error = $error;
    }

    private function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function renderAlerts(): string
    {
        $html = '';

        if (!empty($this->message)) {
            $message = $this->e($this->message);
            $html .= "<div class=\"mvc-alert-success\"><strong>{$message}</strong></div>";
        }

        if (!empty($this->error)) {
            $error = $this->e($this->error);
            $html .= "<div class=\"admin-alert-error\"><strong>{$error}</strong></div>";
        }

        return $html;
    }

    private function renderTeamOptions(int $selectedTeamId): string
    {
        $html = '';

        foreach ($this->teams as $team) {
            $id = (int)($team['id'] ?? 0);
            $name = $this->e($team['name'] ?? 'Team');
            $selected = $id === $selectedTeamId ? 'selected' : '';

            $html .= "<option value=\"{$id}\" {$selected}>{$name}</option>";
        }

        return $html;
    }

    private function renderRoleOptions(string $selectedRole): string
    {
        $roles = [
            'admin' => 'Адміністратор',
            'manager' => 'Менеджер',
            'employee' => 'Співробітник',
        ];

        $html = '';

        foreach ($roles as $role => $title) {
            $selected = $role === $selectedRole ? 'selected' : '';
            $safeRole = $this->e($role);
            $safeTitle = $this->e($title);

            $html .= "<option value=\"{$safeRole}\" {$selected}>{$safeTitle}</option>";
        }

        return $html;
    }

    private function renderUserRows(): string
    {
        if (empty($this->users)) {
            return '<p class="admin-empty">Користувачів поки немає.</p>';
        }

        $rows = '';

        foreach ($this->users as $user) {
            $id = (int)($user['id'] ?? 0);
            $safeId = $this->e($id);
            $name = $this->e($user['name'] ?? '');
            $email = $this->e($user['email'] ?? '');
            $role = $user['role'] ?? 'employee';
            $teamId = (int)($user['team_id'] ?? 1);

            $roleOptions = $this->renderRoleOptions($role);
            $teamOptions = $this->renderTeamOptions($teamId);

            $deleteDisabled = $id === (int)($_SESSION['user_id'] ?? 0)
                ? 'disabled'
                : '';

            $rows .= <<<HTML
            <tr>
                <td>
                    <strong>{$name}</strong>
                    <span class="admin-subtext">ID: {$safeId}</span>
                </td>

                <td>{$email}</td>

                <td>
                    <form method="POST" class="admin-user-form">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="{$safeId}">

                        <select name="role">
                            {$roleOptions}
                        </select>
                </td>

                <td>
                        <select name="team_id">
                            {$teamOptions}
                        </select>
                </td>

                <td>
                        <button type="submit" class="admin-save-btn">Зберегти</button>
                    </form>
                </td>

                <td>
                    <form method="POST" class="admin-delete-form">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="{$safeId}">
                        <button type="submit" class="admin-delete-btn" {$deleteDisabled}>Видалити</button>
                    </form>
                </td>
            </tr>
HTML;
        }

        return <<<HTML
        <div class="mvc-table-wrapper">
            <table class="mvc-table admin-table">
                <thead>
                    <tr>
                        <th>Користувач</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Команда</th>
                        <th>Зберегти</th>
                        <th>Видалити</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
HTML;
    }

    private function renderTableTabs(): string
    {
        $html = '<div class="admin-table-tabs">';

        foreach ($this->allowedTables as $table) {
            $safeTable = $this->e($table);
            $active = $table === $this->selectedTable ? 'active' : '';

            $html .= "<a href=\"admin.php?table={$safeTable}\" class=\"{$active}\">{$safeTable}</a>";
        }

        $html .= '</div>';

        return $html;
    }

    private function renderCreateForm(): string
    {
        if (empty($this->tableEditableColumns)) {
            return '';
        }

        $fields = '';

        foreach ($this->tableEditableColumns as $column) {
            $name = $this->e($column['name']);
            $type = $this->e($column['type'] ?? 'TEXT');

            $inputType = $name === 'password' ? 'password' : 'text';

            $fields .= <<<HTML
            <label>
                {$name}
                <input 
                    type="{$inputType}" 
                    name="data[{$name}]" 
                    placeholder="{$type}"
                >
            </label>
HTML;
        }

        $table = $this->e($this->selectedTable);

        return <<<HTML
        <form method="POST" class="admin-crud-create-form">
            <input type="hidden" name="action" value="admin_create_row">
            <input type="hidden" name="table" value="{$table}">

            {$fields}

            <button type="submit">Додати запис</button>
        </form>
HTML;
    }

    private function renderCrudTable(): string
    {
        if (empty($this->tableColumns)) {
            return '<p class="admin-empty">Немає колонок для відображення.</p>';
        }

        if (empty($this->tableRows)) {
            return '<p class="admin-empty">У таблиці поки немає записів.</p>';
        }

        $headers = '';

        foreach ($this->tableColumns as $column) {
            $name = $this->e($column['name']);
            $headers .= "<th>{$name}</th>";
        }

        $headers .= '<th>Зберегти</th><th>Видалити</th>';

        $rowsHtml = '';

        foreach ($this->tableRows as $row) {
            $rowId = (int)($row['id'] ?? 0);
            $safeRowId = $this->e($rowId);
            $table = $this->e($this->selectedTable);

            $rowsHtml .= '<tr>';

            $rowsHtml .= <<<HTML
            <form method="POST">
                <input type="hidden" name="action" value="admin_update_row">
                <input type="hidden" name="table" value="{$table}">
                <input type="hidden" name="row_id" value="{$safeRowId}">
HTML;

            foreach ($this->tableColumns as $column) {
                $columnName = $column['name'];
                $safeColumnName = $this->e($columnName);
                $value = $this->e($row[$columnName] ?? '');
                $editable = !in_array($columnName, ['id'], true);

                if ($editable) {
                    $inputType = $columnName === 'password' ? 'password' : 'text';

                    $cell = <<<HTML
                    <input 
                        type="{$inputType}" 
                        name="data[{$safeColumnName}]" 
                        value="{$value}"
                        placeholder="{$safeColumnName}"
                    >
HTML;
                } else {
                    $cell = "<strong>{$value}</strong>";
                }

                $rowsHtml .= "<td>{$cell}</td>";
            }

            $rowsHtml .= <<<HTML
                <td>
                    <button type="submit" class="admin-save-btn">Зберегти</button>
                </td>
            </form>
HTML;

            $disabled = ($this->selectedTable === 'users' && $rowId === (int)($_SESSION['user_id'] ?? 0))
                ? 'disabled'
                : '';

            $rowsHtml .= <<<HTML
                <td>
                    <form method="POST" class="admin-delete-form">
                        <input type="hidden" name="action" value="admin_delete_row">
                        <input type="hidden" name="table" value="{$table}">
                        <input type="hidden" name="row_id" value="{$safeRowId}">
                        <button type="submit" class="admin-delete-btn" {$disabled}>Видалити</button>
                    </form>
                </td>
            </tr>
HTML;
        }

        return <<<HTML
        <div class="mvc-table-wrapper admin-crud-wrapper">
            <table class="mvc-table admin-crud-table">
                <thead>
                    <tr>
                        {$headers}
                    </tr>
                </thead>
                <tbody>
                    {$rowsHtml}
                </tbody>
            </table>
        </div>
HTML;
    }

    private function renderCrudPanel(): string
    {
        $tabsHtml = $this->renderTableTabs();
        $createFormHtml = $this->renderCreateForm();
        $crudTableHtml = $this->renderCrudTable();
        $selectedTable = $this->e($this->selectedTable);

        return <<<HTML
        <section class="mvc-panel">
            <div class="mvc-panel-header">
                <div>
                    <h2>Універсальний CRUD</h2>
                    <p>
                        Керований доступ до основних таблиць системи. Активна таблиця: <strong>{$selectedTable}</strong>.
                    </p>
                </div>
            </div>

            {$tabsHtml}

            <div class="admin-crud-create-box">
                <h3>Новий запис</h3>
                {$createFormHtml}
            </div>

            <div class="admin-crud-table-box">
                <h3>Записи таблиці</h3>
                {$crudTableHtml}
            </div>
        </section>
HTML;
    }

    public function renderBody()
    {
        $totalUsers = count($this->users);
        $totalTeams = count($this->teams);
        $adminCount = count(array_filter($this->users, fn($u) => ($u['role'] ?? '') === 'admin'));
        $managerCount = count(array_filter($this->users, fn($u) => ($u['role'] ?? '') === 'manager'));

        $alertsHtml = $this->renderAlerts();
        $usersHtml = $this->renderUserRows();
        $crudPanelHtml = $this->renderCrudPanel();

        echo '<link rel="stylesheet" href="css/admin.css">';

        echo <<<HTML
        <main class="mvc-page admin-page">
            <section class="mvc-shell">
                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">Global Admin</div>
                        <h1>Адміністрування ProHour</h1>
                        <p>
                            Глобальна панель керування користувачами, ролями, командами та основними таблицями системи.
                        </p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>Рівень доступу</span>
                        <strong>Admin</strong>
                    </div>
                </div>

                <div class="mvc-stats-grid">
                    <div class="mvc-stat-card">
                        <span>Користувачі</span>
                        <strong>{$totalUsers}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Команди</span>
                        <strong>{$totalTeams}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Адміни</span>
                        <strong>{$adminCount}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Менеджери</span>
                        <strong>{$managerCount}</strong>
                    </div>
                </div>

                {$alertsHtml}

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Користувачі та ролі</h2>
                            <p>
                                Змінюйте рівні доступу користувачів і прив’язку до команди.
                            </p>
                        </div>
                    </div>

                    {$usersHtml}
                </section>

                {$crudPanelHtml}
            </section>
        </main>
HTML;
    }
}

$page = new AdminPage(
    'Адмін | ProHour',
    $translator,
    $users,
    $teams,
    $allowedTables,
    $selectedTable,
    $tableColumns,
    $tableEditableColumns,
    $tableRows,
    $message,
    $error
);

$page->render();