<?php

session_start();
date_default_timezone_set('Europe/Kyiv');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "classes/Database.php";
require_once "classes/UserRepository.php";

use App\Database\Database;
use App\Repositories\UserRepository;

$db = new Database();
$userRepository = new UserRepository($db->getConnection());

$users = array_filter(
    $userRepository->getUsers(),
    fn($user) => (int)$user['id'] !== (int)$_SESSION['user_id']
);

$currentUser = [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? 'User',
    'email' => $_SESSION['user_email'] ?? '',
];

$currentUserJson = json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$usersJson = json_encode(array_values($users), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Чат | ProHour</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chat.css">
</head>
<body>
    <main class="chat-card">
        <a href="tasks.php" class="back-link">← Назад до задач</a>

        <h1>Чат ProHour</h1>

        <div id="chatStatus" class="chat-status">Підключення до WebSocket-сервера...</div>

        <?php if (empty($users)): ?>
            <div class="empty-users">
                У системі поки немає інших користувачів для чату.
            </div>
        <?php endif; ?>

        <div class="chat-controls">
            <select id="recipientSelect">
                <option value="">Оберіть отримувача</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <input id="messageInput" type="text" placeholder="Напишіть повідомлення...">

            <button id="sendButton">Надіслати</button>
        </div>

        <section id="messages" class="messages"></section>
    </main>

    <script>
        const currentUser = <?= $currentUserJson ?>;
        const users = <?= $usersJson ?>;

        const socket = new WebSocket('ws://localhost:8080');

        const chatStatus = document.getElementById('chatStatus');
        const recipientSelect = document.getElementById('recipientSelect');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const messages = document.getElementById('messages');

        socket.addEventListener('open', () => {
            chatStatus.textContent = 'WebSocket-з’єднання активне.';

            socket.send(JSON.stringify({
                type: 'auth',
                user_id: currentUser.id,
                user_name: currentUser.name,
                user_email: currentUser.email
            }));
        });

        socket.addEventListener('close', () => {
            chatStatus.textContent = 'WebSocket-з’єднання закрито. Запустіть php bin/ws-server.php.';
        });

        socket.addEventListener('error', () => {
            chatStatus.textContent = 'Помилка WebSocket-з’єднання.';
        });

        socket.addEventListener('message', (event) => {
            const data = JSON.parse(event.data);

            if (data.type === 'private_message') {
                renderPrivateMessage(data);
            }

            if (data.type === 'system') {
                renderSystemMessage(data.message, data.created_at);
            }

            if (data.type === 'task_notification') {
                renderNotification(data);
            }

            if (data.type === 'auth_success') {
                renderSystemMessage(data.message, data.created_at);
            }

            if (data.type === 'error') {
                renderSystemMessage(data.message, new Date().toISOString());
            }
        });

        sendButton.addEventListener('click', sendMessage);

        messageInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });

        function sendMessage() {
            const toUserId = recipientSelect.value;
            const message = messageInput.value.trim();

            if (!toUserId || !message) {
                renderSystemMessage('Оберіть отримувача і введіть повідомлення.', new Date().toISOString());
                return;
            }

            socket.send(JSON.stringify({
                type: 'private_message',
                to_user_id: toUserId,
                message: message
            }));

            messageInput.value = '';
        }

        function renderPrivateMessage(data) {
            const element = document.createElement('div');
            element.className = data.is_own ? 'message own' : 'message';

            const sender = data.is_own ? 'Ви' : data.from_user_name;
            const date = formatDate(data.created_at);

            element.innerHTML = `
                <div class="message-meta">${escapeHtml(sender)} · ${escapeHtml(date)}</div>
                <div>${escapeHtml(data.message)}</div>
            `;

            messages.appendChild(element);
            messages.scrollTop = messages.scrollHeight;
        }

        function renderSystemMessage(text, createdAt) {
            const element = document.createElement('div');
            element.className = 'message system';

            element.innerHTML = `
                <div class="message-meta">Система · ${escapeHtml(formatDate(createdAt))}</div>
                <div>${escapeHtml(text)}</div>
            `;

            messages.appendChild(element);
            messages.scrollTop = messages.scrollHeight;
        }

        function renderNotification(data) {
            const element = document.createElement('div');
            element.className = 'message notification';

            const taskPart = data.task_id ? ` · задача #${data.task_id}` : '';

            element.innerHTML = `
                <div class="message-meta">Нотифікація · ${escapeHtml(formatDate(data.created_at))}</div>
                <div>${escapeHtml(data.user_name)}: ${escapeHtml(data.description)}${escapeHtml(taskPart)}</div>
            `;

            messages.appendChild(element);
            messages.scrollTop = messages.scrollHeight;
        }

        function formatDate(value) {
            if (!value) {
                return '';
            }

            return new Date(value).toLocaleString('uk-UA');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
</body>
</html>