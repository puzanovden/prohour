<?php

class Page
{
    protected $title;
    protected $t;

    public function __construct($title, Translator $t)
    {
        $this->title = $title;
        $this->t = $t;
    }

    public function renderHeader()
    {
        $activeUa = $this->t->getLang() === 'uk' ? 'active' : '';
        $activeEn = $this->t->getLang() === 'en' ? 'active' : '';

        //☺
        if (isset($_SESSION['user_id'])) {
                $userName = $_SESSION['user_name'] ?? 'User';
                preg_match('/^./u', $userName, $matches);
                $userLetter = $matches[0] ?? '?';

                $userPanel = "
                    <div class=\"user-panel\">
                        <a href=\"tasks.php\" class=\"user-status\">$userName</a>
                        <a href=\"chat.php\" class=\"logout-link\">Чат</a>
                        <div class=\"user-avatar\">$userLetter</div>
                        <a href=\"logout.php\" class=\"logout-link\">Вийти</a>
                    </div>
                ";
            } else {
                $userPanel = "
                    <div class=\"user-panel\">
                        <a href=\"login.php\" class=\"user-status\">Увійти</a>
                        <div class=\"user-avatar\">?</div>
                    </div>
                ";
            }
            //☺

        echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->title}</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body id="for-whom">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header>
        <a href="index.php" class="logo">
            <img src="img/logo.png" alt="ProHour">
        </a>

        <nav>
            <a href="index.php#for-whom">{$this->t->get('nav_for_whom')}</a>
            <a href="index.php#features">{$this->t->get('nav_features')}</a>
            <a href="index.php#workflow">{$this->t->get('nav_how')}</a>
            <a href="index.php#analytics">{$this->t->get('nav_analytics')}</a>
        </nav>

        <div class="lang-switch">
            <a href="?lang=uk" class="lang-btn {$activeUa}">
                <img src="img/ua.svg" alt="UA"> UA
            </a>
            <a href="?lang=en" class="lang-btn {$activeEn}">
                <img src="img/gb.svg" alt="EN"> EN
            </a>
        </div>

        {$userPanel}
<!--         <div class="user-panel">
                <a href="tasks.php" class="user-status">
                    {$this->t->get('status_online')}
                </a>
            <div class="user-avatar">D</div>
        </div> -->
    </header>
HTML;
    }
    
    public function renderBody()
    {
    }

    public function renderFooter()
    {
        $realtimeScript = $this->buildRealtimeNotificationScript();

        echo <<<HTML
        <footer>
            <div>ProHour © 2026</div>
            <div>Smart Time Tracking System</div>
        </footer>

        <div id="realtime-notifications"></div>

        <script>
            const reveals = document.querySelectorAll('.reveal');
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if(entry.isIntersecting){
                        entry.target.classList.add('active');
                    }
                });
            });
            reveals.forEach(el => observer.observe(el));
        </script>

        {$realtimeScript}
    </body>
    </html>
    HTML;
    }

    protected function buildRealtimeNotificationScript(): string
    {
        if (!isset($_SESSION['user_id'])) {
            return '';
        }

        $currentUser = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
        ];

        $pendingNotification = $_SESSION['pending_notification'] ?? null;
        unset($_SESSION['pending_notification']);

        $currentUserJson = json_encode(
            $currentUser,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $pendingNotificationJson = json_encode(
            $pendingNotification,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        return <<<HTML
    <script>
    (function () {
        const currentUser = {$currentUserJson};
        const pendingNotification = {$pendingNotificationJson};

        let socket;

        try {
            socket = new WebSocket('ws://localhost:8080');
        } catch (error) {
            console.warn('WebSocket недоступний:', error);
            return;
        }

        socket.addEventListener('open', () => {
            socket.send(JSON.stringify({
                type: 'auth',
                user_id: currentUser.id,
                user_name: currentUser.name,
                user_email: currentUser.email
            }));

            if (pendingNotification) {
                socket.send(JSON.stringify({
                    type: 'task_notification',
                    action: pendingNotification.action,
                    description: pendingNotification.description,
                    task_id: pendingNotification.task_id
                }));
            }
        });

        socket.addEventListener('message', (event) => {
            const data = JSON.parse(event.data);

            if (data.type === 'task_notification') {
                showRealtimeNotification(data);
            }
        });

        function showRealtimeNotification(data) {
            const container = getNotificationContainer();

            const notification = document.createElement('div');
            notification.className = 'realtime-notification';

            const taskPart = data.task_id ? ' · задача #' + data.task_id : '';

            notification.innerHTML =
                '<strong>Оновлення ProHour</strong><br>' +
                escapeHtml(data.user_name) + ': ' +
                escapeHtml(data.description) +
                escapeHtml(taskPart);

            container.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 7000);
        }

        function getNotificationContainer() {
            let container = document.getElementById('realtime-notifications');

            if (!container) {
                container = document.createElement('div');
                container.id = 'realtime-notifications';
                document.body.appendChild(container);
            }

            return container;
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    })();
    </script>
    HTML;
    }

    public function render()
    {
        $this->renderHeader();
        $this->renderBody();
        $this->renderFooter();
    }
}