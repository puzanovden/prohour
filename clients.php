<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: clients.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";
require_once "classes/ClientRepository.php";
require_once "classes/Page.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;
use App\Repositories\ClientRepository;

AuthService::requireAuth();

$translator = new Translator($currentLang);

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$clientRepository = new ClientRepository($dbConnection);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $error = '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = $translator->get('clients_err_name');
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $translator->get('clients_err_email');
        } else {
            $created = $clientRepository->createClient(
                $name,
                $contactPerson,
                $email,
                $phone,
                $description
            );

            $_SESSION['flash_message'] = $created
                ? $translator->get('clients_msg_created')
                : $translator->get('clients_msg_create_failed');
        }
    }

    if ($action === 'delete') {
        $clientId = (int)($_POST['client_id'] ?? 0);

        if ($clientId > 0) {
            $deleted = $clientRepository->deleteClient($clientId);

            $_SESSION['flash_message'] = $deleted
                ? $translator->get('clients_msg_deleted')
                : $translator->get('clients_msg_delete_failed');
        }
    }

    if (!empty($error)) {
        $_SESSION['flash_error'] = $error;
    }

    header("Location: clients.php");
    exit;
}

$clients = $clientRepository->getClients();

$message = $_SESSION['flash_message'] ?? '';
$error = $_SESSION['flash_error'] ?? '';

unset($_SESSION['flash_message']);
unset($_SESSION['flash_error']);

class ClientsPage extends Page
{
    private array $clients;
    private string $message;
    private string $error;

    public function __construct(
        string $title,
        Translator $t,
        array $clients,
        string $message = '',
        string $error = ''
    ) {
        parent::__construct($title, $t);

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
            $html .= "<div class=\"clients-alert-error\"><strong>{$error}</strong></div>";
        }

        return $html;
    }

    private function renderClientRows(): string
    {
        if (empty($this->clients)) {
            return '<p class="clients-empty">' . $this->e($this->t->get('clients_empty')) . '</p>';
        }

        $rows = '';

        foreach ($this->clients as $client) {
            $id = $this->e($client['id'] ?? '');
            $name = $this->e($client['name'] ?? '');
            $contactPerson = $this->e($client['contact_person'] ?? '-');
            $email = $this->e($client['email'] ?? '-');
            $phone = $this->e($client['phone'] ?? '-');

            $description = trim($client['description'] ?? '');
            $description = $description !== ''
                ? $this->e($description)
                : $this->e($this->t->get('clients_no_description'));

            $deleteText = $this->e($this->t->get('clients_delete_btn'));

            $rows .= <<<HTML
            <tr>
                <td>
                    <strong>{$name}</strong>
                    <span class="clients-table-description">{$description}</span>
                </td>
                <td>{$contactPerson}</td>
                <td>{$email}</td>
                <td>{$phone}</td>
                <td>
                    <form method="POST" class="clients-inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="client_id" value="{$id}">
                        <button type="submit" class="clients-delete-btn">{$deleteText}</button>
                    </form>
                </td>
            </tr>
HTML;
        }

        $clientTitle = $this->e($this->t->get('clients_table_client'));
        $contactTitle = $this->e($this->t->get('clients_table_contact'));
        $phoneTitle = $this->e($this->t->get('clients_table_phone'));
        $actionTitle = $this->e($this->t->get('clients_table_action'));

        return <<<HTML
        <div class="mvc-table-wrapper">
            <table class="mvc-table clients-table">
                <thead>
                    <tr>
                        <th>{$clientTitle}</th>
                        <th>{$contactTitle}</th>
                        <th>Email</th>
                        <th>{$phoneTitle}</th>
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
        $totalClients = count($this->clients);

        $alertsHtml = $this->renderAlerts();
        $clientRowsHtml = $this->renderClientRows();

        echo '<link rel="stylesheet" href="css/clients.css">';

        echo <<<HTML
        <main class="mvc-page clients-page">
            <section class="mvc-shell">
                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">{$this->e($this->t->get('clients_eyebrow'))}</div>
                        <h1>{$this->e($this->t->get('clients_hero_title'))}</h1>
                        <p>{$this->e($this->t->get('clients_hero_text'))}</p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>{$this->e($this->t->get('clients_total'))}</span>
                        <strong>{$totalClients}</strong>
                    </div>
                </div>

                {$alertsHtml}

                

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>{$this->e($this->t->get('clients_list_title'))}</h2>
                            <p>{$this->e($this->t->get('clients_list_text'))}</p>
                        </div>
                    </div>

                    {$clientRowsHtml}
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>{$this->e($this->t->get('clients_new_title'))}</h2>
                            <p>{$this->e($this->t->get('clients_new_text'))}</p>
                        </div>
                    </div>

                    <form method="POST" class="mvc-form clients-form">
                        <input type="hidden" name="action" value="create">

                        <label>
                            {$this->e($this->t->get('clients_name'))}
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="{$this->e($this->t->get('clients_name_ph'))}" 
                                required
                            >
                        </label>

                        <label>
                            {$this->e($this->t->get('clients_contact_person'))}
                            <input 
                                type="text" 
                                name="contact_person" 
                                placeholder="{$this->e($this->t->get('clients_contact_person_ph'))}"
                            >
                        </label>

                        <label>
                            {$this->e($this->t->get('clients_email'))}
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="client@example.com" 
                                autocomplete="email"
                            >
                        </label>

                        <label>
                            {$this->e($this->t->get('clients_phone'))}
                            <input 
                                type="text" 
                                name="phone" 
                                placeholder="+380..."
                            >
                        </label>

                        <label class="clients-form-wide">
                            {$this->e($this->t->get('clients_description'))}
                            <textarea 
                                name="description" 
                                placeholder="{$this->e($this->t->get('clients_description_ph'))}"
                            ></textarea>
                        </label>

                        <button type="submit">{$this->e($this->t->get('clients_add_btn'))}</button>
                    </form>
                </section>
            </section>
        </main>
HTML;
    }
}

$page = new ClientsPage(
    $translator->get('clients_title'),
    $translator,
    $clients,
    $message,
    $error
);

$page->render();