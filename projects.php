<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: projects.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";
require_once "classes/ProjectRepository.php";
require_once "classes/ClientRepository.php";
require_once "classes/Page.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;
use App\Repositories\ProjectRepository;
use App\Repositories\ClientRepository;

AuthService::requireAuth();

$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$authService = new AuthService($dbConnection);
$currentUser = $authService->getCurrentUser();
$teamId = (int)($currentUser['team_id'] ?? 1);

$projectRepository = new ProjectRepository($dbConnection);
$clientRepository = new ClientRepository($dbConnection);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $error = '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $clientId = (int)($_POST['client_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if (empty($name)) {
            $error = $translator->get('projects_err_name');
        } else {
            $clientIdValue = $clientId > 0 ? $clientId : null;

            $projectId = $projectRepository->createProject(
                $teamId,
                $clientIdValue,
                $name,
                $description,
                $status
            );

            $_SESSION['flash_message'] = $projectId > 0
                ? $translator->get('projects_msg_created')
                : $translator->get('projects_msg_create_failed');
        }
    }

    if ($action === 'delete') {
        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($projectId > 0) {
            $deleted = $projectRepository->deleteProject($projectId, $teamId);

            $_SESSION['flash_message'] = $deleted
                ? $translator->get('projects_msg_deleted')
                : $translator->get('projects_msg_delete_failed');
        }
    }

    if (!empty($error)) {
        $_SESSION['flash_error'] = $error;
    }

    header("Location: projects.php");
    exit;
}

$projects = $projectRepository->getProjectsByTeamId($teamId);
$clients = method_exists($clientRepository, 'getClientsByTeamId')
    ? $clientRepository->getClientsByTeamId($teamId)
    : $clientRepository->getClients();

$message = $_SESSION['flash_message'] ?? '';
$error = $_SESSION['flash_error'] ?? '';

unset($_SESSION['flash_message']);
unset($_SESSION['flash_error']);

class ProjectsPage extends Page
{
    private array $projects;
    private array $clients;
    private string $message;
    private string $error;

    public function __construct(
        string $title,
        Translator $t,
        array $projects,
        array $clients,
        string $message = '',
        string $error = ''
    ) {
        parent::__construct($title, $t);

        $this->projects = $projects;
        $this->clients = $clients;
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
            $html .= "<div class=\"projects-alert-error\"><strong>{$error}</strong></div>";
        }

        return $html;
    }

    private function renderClientOptions(): string
    {
        $html = '<option value="">' . $this->e($this->t->get('projects_no_client')) . '</option>';

        foreach ($this->clients as $client) {
            $id = $this->e($client['id'] ?? '');
            $name = $this->e($client['name'] ?? '');

            $html .= "<option value=\"{$id}\">{$name}</option>";
        }

        return $html;
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'paused' => $this->t->get('projects_status_paused'),
            'completed' => $this->t->get('projects_status_completed'),
            default => $this->t->get('projects_status_active'),
        };
    }

    private function getStatusClass(string $status): string
    {
        return match ($status) {
            'paused' => 'mvc-status-paused',
            'completed' => 'mvc-status-completed',
            default => 'mvc-status-active',
        };
    }

    private function renderProjectRows(): string
    {
        if (empty($this->projects)) {
            return '<p class="projects-empty">' . $this->e($this->t->get('projects_empty')) . '</p>';
        }

        $rows = '';

        foreach ($this->projects as $project) {
            $id = $this->e($project['id'] ?? '');
            $name = $this->e($project['name'] ?? '');
            $clientName = $this->e($project['client_name'] ?? $this->t->get('projects_no_client'));
            $status = $project['status'] ?? 'active';
            $statusText = $this->e($this->getStatusText($status));
            $statusClass = $this->e($this->getStatusClass($status));
            $createdAt = $this->e($project['created_at'] ?? '-');

            $description = trim($project['description'] ?? '');
            $description = $description !== ''
                ? $this->e($description)
                : $this->e($this->t->get('projects_no_description'));

            $deleteText = $this->e($this->t->get('projects_delete_btn'));

            $rows .= <<<HTML
            <tr>
                <td>
                    <strong>{$name}</strong>
                    <span class="projects-table-description">{$description}</span>
                </td>
                <td>{$clientName}</td>
                <td>
                    <span class="mvc-status-pill {$statusClass}">{$statusText}</span>
                </td>
                <td>{$createdAt}</td>
                <td>
                    <form method="POST" class="projects-inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="project_id" value="{$id}">
                        <button type="submit" class="projects-delete-btn">{$deleteText}</button>
                    </form>
                </td>
            </tr>
HTML;
        }

        $projectTitle = $this->e($this->t->get('projects_table_project'));
        $clientTitle = $this->e($this->t->get('projects_table_client'));
        $statusTitle = $this->e($this->t->get('projects_table_status'));
        $createdTitle = $this->e($this->t->get('projects_table_created'));
        $actionTitle = $this->e($this->t->get('projects_table_action'));

        return <<<HTML
        <div class="mvc-table-wrapper">
            <table class="mvc-table projects-table">
                <thead>
                    <tr>
                        <th>{$projectTitle}</th>
                        <th>{$clientTitle}</th>
                        <th>{$statusTitle}</th>
                        <th>{$createdTitle}</th>
                        <th>{$actionTitle}</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
HTML;
    }

    public function renderBody()
    {
        $totalProjects = count($this->projects);

        $alertsHtml = $this->renderAlerts();
        $clientOptions = $this->renderClientOptions();
        $projectRowsHtml = $this->renderProjectRows();

        echo '<link rel="stylesheet" href="css/projects.css">';

        echo <<<HTML
        <main class="mvc-page projects-page">
            <section class="mvc-shell">
                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">{$this->e($this->t->get('projects_eyebrow'))}</div>
                        <h1>{$this->e($this->t->get('projects_hero_title'))}</h1>
                        <p>{$this->e($this->t->get('projects_hero_text'))}</p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>{$this->e($this->t->get('projects_total'))}</span>
                        <strong>{$totalProjects}</strong>
                    </div>
                </div>

                {$alertsHtml}

                

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>{$this->e($this->t->get('projects_list_title'))}</h2>
                            <p>{$this->e($this->t->get('projects_list_text'))}</p>
                        </div>
                    </div>

                    {$projectRowsHtml}
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>{$this->e($this->t->get('projects_new_title'))}</h2>
                            <p>{$this->e($this->t->get('projects_new_text'))}</p>
                        </div>
                    </div>

                    <form method="POST" class="mvc-form projects-form">
                        <input type="hidden" name="action" value="create">

                        <label>
                            {$this->e($this->t->get('projects_name'))}
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="{$this->e($this->t->get('projects_name_ph'))}" 
                                required
                            >
                        </label>

                        <label>
                            {$this->e($this->t->get('projects_client'))}
                            <select name="client_id">
                                {$clientOptions}
                            </select>
                        </label>

                        <label>
                            {$this->e($this->t->get('projects_status'))}
                            <select name="status">
                                <option value="active">{$this->e($this->t->get('projects_status_active'))}</option>
                                <option value="paused">{$this->e($this->t->get('projects_status_paused'))}</option>
                                <option value="completed">{$this->e($this->t->get('projects_status_completed'))}</option>
                            </select>
                        </label>

                        <label class="projects-form-wide">
                            {$this->e($this->t->get('projects_description'))}
                            <textarea 
                                name="description" 
                                placeholder="{$this->e($this->t->get('projects_description_ph'))}"
                            ></textarea>
                        </label>

                        <button type="submit">{$this->e($this->t->get('projects_add_btn'))}</button>
                    </form>
                </section>
            </section>
        </main>
HTML;
    }
}

$page = new ProjectsPage(
    $translator->get('projects_title'),
    $translator,
    $projects,
    $clients,
    $message,
    $error
);

$page->render();