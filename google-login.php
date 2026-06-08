<?php

session_start();

date_default_timezone_set('Europe/Kyiv');

require_once __DIR__ . '/vendor/autoload.php';
require_once "classes/Database.php";
require_once "classes/AuthService.php";
require_once "classes/MigrationService.php";

use App\Database\Database;
use App\Services\AuthService;
use App\Services\MigrationService;
use Google\Client;

header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Google config is missing.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require $configPath;
$caPath = __DIR__ . '/certs/cacert.pem';

if (file_exists($caPath)) {
    ini_set('curl.cainfo', $caPath);
    ini_set('openssl.cafile', $caPath);
}
$clientId = $config['google_client_id'] ?? '';

$credential = $_POST['credential'] ?? '';

$caPath = __DIR__ . '/certs/cacert.pem';

if (file_exists($caPath)) {
    ini_set('curl.cainfo', $caPath);
    ini_set('openssl.cafile', $caPath);
}

if ($clientId === '' || $credential === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Google credential is missing.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$client = new Client([
    'client_id' => $clientId,
]);

try {
    $payload = $client->verifyIdToken($credential);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Google token verification failed.',
        'debug' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$payload) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Google token.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$authService = new AuthService($dbConnection);

if (!$authService->loginWithGooglePayload($payload)) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot login with Google.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'redirect' => 'tasks.php',
], JSON_UNESCAPED_UNICODE);