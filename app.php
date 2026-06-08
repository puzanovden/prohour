<?php

session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];

    $route = $_GET['route'] ?? 'analytics';
    $date = $_GET['date'] ?? date('Y-m-d');

    header("Location: app.php?route={$route}&date={$date}");
    exit;
}

date_default_timezone_set('Europe/Kyiv');

require_once __DIR__ . '/classes/bootstrap.php';

use App\Adapters\SqliteDatabaseAdapter;
use App\Controllers\AnalyticsController;
use App\Controllers\TaskMvcController;
use App\Core\Router;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$databaseAdapter = new SqliteDatabaseAdapter();

$router = Router::getInstance();

$router->get('analytics', [new AnalyticsController($databaseAdapter), 'index']);
$router->get('tasks/create', [new TaskMvcController($databaseAdapter), 'createForm']);
$router->post('tasks/create', [new TaskMvcController($databaseAdapter), 'store']);

$router->dispatch($_GET['route'] ?? 'analytics', $_SERVER['REQUEST_METHOD']);