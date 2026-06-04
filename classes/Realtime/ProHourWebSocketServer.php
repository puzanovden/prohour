<?php

namespace App\Realtime;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;
use Throwable;

class ProHourWebSocketServer implements MessageComponentInterface
{
    private SplObjectStorage $clients;

    /**
     * userId => ConnectionInterface
     */
    private array $users = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
        echo "ProHour WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, [
            'user_id' => null,
            'user_name' => null,
            'user_email' => null,
        ]);

        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        if (!is_array($data) || !isset($data['type'])) {
            $this->sendJson($from, [
                'type' => 'error',
                'message' => 'Некоректний формат повідомлення.',
            ]);

            return;
        }

        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;

            case 'private_message':
                $this->handlePrivateMessage($from, $data);
                break;

            case 'task_notification':
                $this->handleTaskNotification($from, $data);
                break;

            default:
                $this->sendJson($from, [
                    'type' => 'error',
                    'message' => 'Невідомий тип повідомлення.',
                ]);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $clientData = $this->clients[$conn] ?? null;

        if ($clientData && !empty($clientData['user_id'])) {
            $userId = (string)$clientData['user_id'];
            unset($this->users[$userId]);

            $this->broadcast([
                'type' => 'system',
                'message' => "{$clientData['user_name']} відключився від ProHour.",
                'created_at' => date('c'),
            ], $conn);
        }

        $this->clients->detach($conn);

        echo "Connection closed: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, Throwable $e): void
    {
        echo "WebSocket error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleAuth(ConnectionInterface $conn, array $data): void
    {
        $userId = trim((string)($data['user_id'] ?? ''));
        $userName = trim((string)($data['user_name'] ?? ''));
        $userEmail = trim((string)($data['user_email'] ?? ''));

        if ($userId === '' || $userName === '') {
            $this->sendJson($conn, [
                'type' => 'error',
                'message' => 'Неможливо авторизувати WebSocket-клієнт.',
            ]);

            return;
        }

        $this->clients[$conn] = [
            'user_id' => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail,
        ];

        $this->users[$userId] = $conn;

        $this->sendJson($conn, [
            'type' => 'auth_success',
            'message' => 'WebSocket-з’єднання встановлено.',
            'created_at' => date('c'),
        ]);

        $this->broadcast([
            'type' => 'system',
            'message' => "{$userName} підключився до ProHour.",
            'created_at' => date('c'),
        ], $conn);
    }

    private function handlePrivateMessage(ConnectionInterface $from, array $data): void
    {
        $sender = $this->clients[$from] ?? null;

        if (!$this->isAuthenticated($from)) {
            $this->sendJson($from, [
                'type' => 'error',
                'message' => 'Спочатку потрібно авторизувати WebSocket-клієнт.',
            ]);

            return;
        }

        $toUserId = trim((string)($data['to_user_id'] ?? ''));
        $messageText = trim((string)($data['message'] ?? ''));

        if ($toUserId === '' || $messageText === '') {
            $this->sendJson($from, [
                'type' => 'error',
                'message' => 'Оберіть отримувача і введіть повідомлення.',
            ]);

            return;
        }

        $message = [
            'type' => 'private_message',
            'from_user_id' => $sender['user_id'],
            'from_user_name' => $sender['user_name'],
            'from_user_email' => $sender['user_email'],
            'to_user_id' => $toUserId,
            'message' => $messageText,
            'created_at' => date('c'),
        ];

        if (isset($this->users[$toUserId])) {
            $this->sendJson($this->users[$toUserId], $message);
        }

        $message['is_own'] = true;
        $this->sendJson($from, $message);
    }

    private function handleTaskNotification(ConnectionInterface $from, array $data): void
    {
        if (!$this->isAuthenticated($from)) {
            return;
        }

        $sender = $this->clients[$from];

        $notification = [
            'type' => 'task_notification',
            'action' => (string)($data['action'] ?? 'unknown'),
            'description' => (string)($data['description'] ?? 'Оновлення задачі'),
            'task_id' => (string)($data['task_id'] ?? ''),
            'user_id' => $sender['user_id'],
            'user_name' => $sender['user_name'],
            'user_email' => $sender['user_email'],
            'created_at' => date('c'),
        ];

        $this->broadcast($notification);
    }

    private function isAuthenticated(ConnectionInterface $conn): bool
    {
        $clientData = $this->clients[$conn] ?? null;

        return is_array($clientData) && !empty($clientData['user_id']);
    }

    private function broadcast(array $payload, ?ConnectionInterface $except = null): void
    {
        foreach ($this->clients as $client) {
            if ($except !== null && $client === $except) {
                continue;
            }

            $this->sendJson($client, $payload);
        }
    }

    private function sendJson(ConnectionInterface $conn, array $payload): void
    {
        $conn->send(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}