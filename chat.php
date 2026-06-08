<?php

session_start();

date_default_timezone_set('Europe/Kyiv');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: chat.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Database.php";
require_once "classes/UserRepository.php";
require_once "classes/MigrationService.php";
require_once "classes/Page.php";

use App\Database\Database;
use App\Repositories\UserRepository;
use App\Services\MigrationService;

$db = new Database();
$dbConnection = $db->getConnection();

$migrationService = new MigrationService($dbConnection);
$migrationService->run();

$userRepository = new UserRepository($dbConnection);
$translator = new Translator($currentLang);

$allUsers = $userRepository->getUsers();

$currentUserId = (int)$_SESSION['user_id'];

$users = array_values(array_filter(
    $allUsers,
    fn($user) => (int)$user['id'] !== $currentUserId
));

$currentUser = [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? 'User',
    'email' => $_SESSION['user_email'] ?? '',
    'avatar' => $_SESSION['user_avatar'] ?? '',
];

$currentUserJson = json_encode(
    $currentUser,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$usersJson = json_encode(
    array_values($users),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$pendingNotification = $_SESSION['pending_notification'] ?? null;
unset($_SESSION['pending_notification']);

$pendingNotificationJson = json_encode(
    $pendingNotification,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

class ChatPage extends Page
{
    private array $users;
    private string $currentUserJson;
    private string $usersJson;
    private string $pendingNotificationJson;

    public function __construct(
        string $title,
        Translator $t,
        array $users,
        string $currentUserJson,
        string $usersJson,
        string $pendingNotificationJson
    ) {
        parent::__construct($title, $t);

        $this->users = $users;
        $this->currentUserJson = $currentUserJson;
        $this->usersJson = $usersJson;
        $this->pendingNotificationJson = $pendingNotificationJson;
    }

    private function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function getAvatarHtml(array $user): string
    {
        $name = $this->e($user['name'] ?? 'U');
        $avatar = trim((string)($user['avatar'] ?? ''));

        if (!empty($avatar)) {
            $safeAvatar = $this->e($avatar);
            return "<img src=\"{$safeAvatar}\" alt=\"{$name}\">";
        }

        preg_match('/^./u', $name, $matches);
        $letter = $this->e($matches[0] ?? '?');

        return "<span>{$letter}</span>";
    }

    private function renderTeamList(): string
    {
        if (empty($this->users)) {
            return '<div class="chat-empty-team">У системі поки немає інших користувачів для чату.</div>';
        }

        $html = '';

        foreach ($this->users as $user) {
            $id = $this->e($user['id'] ?? '');
            $name = $this->e($user['name'] ?? 'User');
            $email = $this->e($user['email'] ?? '');
            $avatarHtml = $this->getAvatarHtml($user);

            $html .= <<<HTML
            <button class="chat-team-user" type="button" data-user-id="{$id}">
                <span class="chat-user-avatar">
                    {$avatarHtml}
                </span>

                <span class="chat-user-info">
                    <strong>{$name}</strong>
                    <small>{$email}</small>
                </span>
            </button>
HTML;
        }

        return $html;
    }

    public function renderBody()
    {
        $teamListHtml = $this->renderTeamList();

        echo '<link rel="stylesheet" href="css/chat-mvc.css">';

        echo <<<HTML
        <main class="mvc-page chat-page">
            <section class="mvc-shell chat-shell">
                <div class="mvc-hero chat-hero">
                    <div>
                        <div class="mvc-eyebrow">Team Chat</div>
                        <h1>Чат ProHour</h1>
                        <p>
                            Спілкуйтесь із командою, надсилайте приватні повідомлення та отримуйте системні нотифікації щодо задач.
                        </p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>WebSocket</span>
                        <strong id="chatConnectionBadge">Підключення...</strong>
                    </div>
                </div>

                <section class="chat-layout">
                    <aside class="chat-team-panel">
                        <div class="chat-panel-header">
                            <div>
                                <h2>Команда</h2>
                                <p>Оберіть користувача або загальну кімнату.</p>
                            </div>
                        </div>

                        <button class="chat-team-user active" type="button" data-user-id="team">
                            <span class="chat-user-avatar chat-team-avatar">
                                <span>👥</span>
                            </span>

                            <span class="chat-user-info">
                                <strong>Загальна кімната</strong>
                                <small>Повідомлення всій команді</small>
                            </span>
                        </button>

                        <div class="chat-team-list">
                            {$teamListHtml}
                        </div>
                    </aside>

                    <section class="chat-room-panel">
                        <div class="chat-room-header">
                            <div>
                                <h2 id="chatRoomTitle">Загальна кімната</h2>
                                <p id="chatRoomSubtitle">Повідомлення будуть надіслані всім користувачам команди.</p>
                            </div>

                            <div id="chatStatus" class="chat-status">
                                Підключення до WebSocket-сервера...
                            </div>
                        </div>

                        <section id="messages" class="chat-messages"></section>

                        <div class="chat-compose">
                            <input 
                                id="messageInput" 
                                type="text" 
                                placeholder="Напишіть повідомлення..."
                                autocomplete="off"
                            >

                            <button id="sendButton" type="button">
                                Надіслати
                            </button>
                        </div>
                    </section>
                </section>
            </section>
        </main>

        <script>
            const currentUser = {$this->currentUserJson};
            const users = {$this->usersJson};
            const pendingNotification = {$this->pendingNotificationJson};

            const socket = new WebSocket('ws://localhost:8080');

            const chatStatus = document.getElementById('chatStatus');
            const chatConnectionBadge = document.getElementById('chatConnectionBadge');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const messages = document.getElementById('messages');
            const roomTitle = document.getElementById('chatRoomTitle');
            const roomSubtitle = document.getElementById('chatRoomSubtitle');

            let selectedRecipientId = 'team';
            let currentRoomKey = 'team';

            const userMap = new Map();
            const renderedMessageKeys = new Set();

            const roomMessages = {
                team: []
            };

            users.forEach(user => {
                userMap.set(String(user.id), user);
            });

            userMap.set(String(currentUser.id), currentUser);

            socket.addEventListener('open', () => {
                setConnectionState('WebSocket-з’єднання активне.', 'online');

                socket.send(JSON.stringify({
                    type: 'auth',
                    user_id: currentUser.id,
                    user_name: currentUser.name,
                    user_email: currentUser.email,
                    user_avatar: currentUser.avatar || ''
                }));

                if (pendingNotification) {
                    socket.send(JSON.stringify({
                        type: 'task_notification',
                        action: pendingNotification.action || '',
                        description: pendingNotification.description || '',
                        task_id: pendingNotification.task_id || '',
                        user_id: currentUser.id,
                        user_name: currentUser.name,
                        user_email: currentUser.email,
                        created_at: new Date().toISOString()
                    }));
                }
            });

            socket.addEventListener('close', () => {
                setConnectionState('WebSocket закрито. Запустіть php bin/ws-server.php.', 'offline');
            });

            socket.addEventListener('error', () => {
                setConnectionState('Помилка WebSocket-з’єднання.', 'error');
            });

            socket.addEventListener('message', (event) => {
                const data = JSON.parse(event.data);

                if (data.type === 'private_message') {
                    renderPrivateMessage(data);
                }

                if (data.type === 'team_message') {
                    renderTeamMessage(data);
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

            document.querySelectorAll('.chat-team-user').forEach(button => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('.chat-team-user').forEach(item => {
                        item.classList.remove('active');
                    });

                    button.classList.add('active');

                    selectedRecipientId = button.dataset.userId;

                    if (selectedRecipientId === 'team') {
                        currentRoomKey = 'team';
                        roomTitle.textContent = 'Загальна кімната';
                        roomSubtitle.textContent = 'Повідомлення будуть надіслані всім користувачам команди.';
                    } else {
                        currentRoomKey = 'user_' + selectedRecipientId;

                        const user = userMap.get(String(selectedRecipientId));

                        roomTitle.textContent = user && user.name ? user.name : 'Користувач';
                        roomSubtitle.textContent = user && user.email ? user.email : 'Приватний діалог';
                    }

                    renderCurrentRoom();
                    messageInput.focus();
                });
            });

            sendButton.addEventListener('click', sendMessage);

            messageInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    sendMessage();
                }
            });

            function sendMessage() {
                const message = messageInput.value.trim();

                if (!message) {
                    renderSystemMessage('Введіть повідомлення.', new Date().toISOString());
                    return;
                }

                if (socket.readyState !== WebSocket.OPEN) {
                    renderSystemMessage('WebSocket не підключено. Запустіть php bin/ws-server.php.', new Date().toISOString());
                    return;
                }

                if (selectedRecipientId === 'team') {
                    sendTeamMessage(message);
                } else {
                    sendPrivateMessage(selectedRecipientId, message);
                }

                messageInput.value = '';
            }

            function sendPrivateMessage(toUserId, message) {
                socket.send(JSON.stringify({
                    type: 'private_message',
                    to_user_id: toUserId,
                    message: message
                }));
            }

            function sendTeamMessage(message) {
                socket.send(JSON.stringify({
                    type: 'team_message',
                    message: message
                }));
            }

            function renderPrivateMessage(data) {
                const roomKey = data.is_own
                    ? 'user_' + data.to_user_id
                    : 'user_' + data.from_user_id;

                const messageKey = [
                    'private',
                    data.from_user_id || '',
                    data.to_user_id || '',
                    data.created_at || '',
                    data.message || ''
                ].join('|');

                if (renderedMessageKeys.has(messageKey)) {
                    return;
                }

                renderedMessageKeys.add(messageKey);

                const senderId = data.is_own ? currentUser.id : data.from_user_id;
                const sender = data.is_own ? currentUser : findUserByNameOrId(data.from_user_name, senderId);

                addMessageToRoom(roomKey, {
                    isOwn: Boolean(data.is_own),
                    senderName: data.is_own ? 'Ви' : (data.from_user_name || (sender && sender.name) || 'Користувач'),
                    avatar: data.from_user_avatar || (sender && sender.avatar) || '',
                    text: data.message || '',
                    createdAt: data.created_at,
                    type: 'message'
                });
            }

            function renderTeamMessage(data) {
                const messageKey = [
                    'team',
                    data.from_user_id || '',
                    data.created_at || '',
                    data.message || ''
                ].join('|');

                if (renderedMessageKeys.has(messageKey)) {
                    return;
                }

                renderedMessageKeys.add(messageKey);

                const sender = data.is_own ? currentUser : findUserByNameOrId(data.from_user_name, data.from_user_id);

                addMessageToRoom('team', {
                    isOwn: Boolean(data.is_own),
                    senderName: data.is_own ? 'Ви' : (data.from_user_name || (sender && sender.name) || 'Користувач'),
                    avatar: data.from_user_avatar || (sender && sender.avatar) || '',
                    text: data.message || '',
                    createdAt: data.created_at,
                    type: 'message'
                });
            }

            function renderSystemMessage(text, createdAt) {
                const messageKey = [
                    'system',
                    createdAt || '',
                    text || ''
                ].join('|');

                if (renderedMessageKeys.has(messageKey)) {
                    return;
                }

                renderedMessageKeys.add(messageKey);

                addMessageToRoom('team', {
                    isOwn: false,
                    senderName: 'Система',
                    avatar: '',
                    text: text,
                    createdAt: createdAt,
                    type: 'system'
                });
            }

            function renderNotification(data) {
                const taskPart = data.task_id ? ' · задача #' + data.task_id : '';
                const text = (data.user_name || 'Користувач') + ': ' + (data.description || 'Операція') + taskPart;

                const messageKey = [
                    'notification',
                    data.created_at || '',
                    text
                ].join('|');

                if (renderedMessageKeys.has(messageKey)) {
                    return;
                }

                renderedMessageKeys.add(messageKey);

                addMessageToRoom('team', {
                    isOwn: false,
                    senderName: 'Нотифікація',
                    avatar: '',
                    text: text,
                    createdAt: data.created_at,
                    type: 'notification'
                });
            }

            function addMessageToRoom(roomKey, messageData) {
                if (!roomMessages[roomKey]) {
                    roomMessages[roomKey] = [];
                }

                roomMessages[roomKey].push(messageData);

                if (roomKey === currentRoomKey) {
                    renderCurrentRoom();
                }
            }

            function renderCurrentRoom() {
                messages.innerHTML = '';

                const currentMessages = roomMessages[currentRoomKey] || [];

                currentMessages.forEach(messageData => {
                    renderMessage(messageData, false);
                });

                messages.scrollTop = messages.scrollHeight;
            }

            function renderMessage(data, shouldScroll = true) {
                const row = document.createElement('div');
                row.className = 'chat-message-row';

                if (data.isOwn) {
                    row.classList.add('own');
                }

                if (data.type === 'system') {
                    row.classList.add('system');
                }

                if (data.type === 'notification') {
                    row.classList.add('notification');
                }

                const avatarHtml = createAvatarHtml(data.senderName, data.avatar);
                const date = formatDate(data.createdAt);

                row.innerHTML =
                    '<div class="chat-message-avatar">' +
                        avatarHtml +
                    '</div>' +
                    '<div class="chat-bubble">' +
                        '<div class="chat-bubble-meta">' +
                            '<strong>' + escapeHtml(data.senderName) + '</strong>' +
                            '<span>' + escapeHtml(date) + '</span>' +
                        '</div>' +
                        '<div class="chat-bubble-text">' +
                            escapeHtml(data.text) +
                        '</div>' +
                    '</div>';

                messages.appendChild(row);

                if (shouldScroll) {
                    messages.scrollTop = messages.scrollHeight;
                }
            }

            function createAvatarHtml(name, avatar) {
                if (avatar) {
                    return '<img src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(name) + '">';
                }

                const letter = String(name || '?').trim().charAt(0).toUpperCase();

                return '<span>' + escapeHtml(letter || '?') + '</span>';
            }

            function findUserByNameOrId(name, id) {
                if (id && userMap.has(String(id))) {
                    return userMap.get(String(id));
                }

                return users.find(user => user.name === name) || null;
            }

            function setConnectionState(text, state) {
                chatStatus.textContent = text;
                chatStatus.className = 'chat-status ' + state;

                if (state === 'online') {
                    chatConnectionBadge.textContent = 'Online';
                } else if (state === 'offline') {
                    chatConnectionBadge.textContent = 'Offline';
                } else {
                    chatConnectionBadge.textContent = 'Error';
                }
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
HTML;
    }
}

$page = new ChatPage(
    'Чат | ProHour',
    $translator,
    $users,
    $currentUserJson,
    $usersJson,
    $pendingNotificationJson
);

$page->render();