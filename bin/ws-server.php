<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Realtime/ProHourWebSocketServer.php';

use App\Realtime\ProHourWebSocketServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ProHourWebSocketServer()
        )
    ),
    8080
);

echo "WebSocket server is running on ws://localhost:8080\n";

$server->run();