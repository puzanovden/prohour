<?php
session_start();

date_default_timezone_set('Europe/Kyiv');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: scheduler.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/Page.php";
require_once "classes/RegexHelper.php";
require_once "classes/TaskActionDomLogger.php";

use App\Utils\RegexHelper;
use App\Services\TaskActionDomLogger;

$translator = new Translator($currentLang);

$action = $_POST['action'] ?? '';
$cronTaskName = trim($_POST['cron_task_name'] ?? '');
$postExecuted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'scheduler_toggle') {
        $isActive = ($_POST['cron_is_active'] ?? '0') === '1';

        RegexHelper::manageTaskScheduler(
            'scheduler.txt',
            $cronTaskName,
            $isActive ? 'disable' : 'enable'
        );

        $postExecuted = true;
    }

    if ($action === 'scheduler_schedule') {
        $newTime = trim($_POST['cron_task_time'] ?? '');

        if (!empty($newTime)) {
            RegexHelper::manageTaskScheduler(
                'scheduler.txt',
                $cronTaskName,
                'schedule',
                $newTime
            );
        }

        $postExecuted = true;
    }

    if ($action === 'scheduler_rename') {
        $newCronName = trim($_POST['new_cron_name'] ?? '');
        $newCronName = preg_replace('/[^a-zA-Z0-9_]/', '', $newCronName);

        if (!empty($newCronName)) {
            RegexHelper::manageTaskScheduler(
                'scheduler.txt',
                $cronTaskName,
                'rename',
                $newCronName
            );
        }

        $postExecuted = true;
    }

    if ($postExecuted) {
        $actionLabels = [
            'scheduler_toggle' => 'Перемикання активності фонової задачі',
            'scheduler_schedule' => 'Оновлення часового тригера фонової задачі',
            'scheduler_rename' => 'Зміна системного імені фонової задачі'
        ];

        $currentActionText = $actionLabels[$action] ?? 'Операція з автоматизацією';

        $logLine = "[" . date('Y-m-d H:i:s') . "] " . $currentActionText . "\n";
        file_put_contents('log.txt', $logLine, FILE_APPEND);

        $taskActionDomLogger = new TaskActionDomLogger('data/task-actions.xml');

        $taskActionDomLogger->log([
            'type' => $action,
            'description' => $currentActionText,
            'user_id' => $_SESSION['user_id'] ?? '',
            'user_name' => $_SESSION['user_name'] ?? '',
            'user_email' => $_SESSION['user_email'] ?? '',
            'task_id' => '',
            'scheduler_task' => $cronTaskName ?: '',
        ]);

        $_SESSION['pending_notification'] = [
            'action' => $action,
            'description' => $currentActionText,
            'task_id' => '',
        ];
    }

    header("Location: scheduler.php#scheduler");
    exit;
}

$schedulerData = [];

if (file_exists('scheduler.txt')) {
    $lines = file(
        'scheduler.txt',
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    foreach ($lines as $line) {
        if (preg_match("/^#\s*([^=]+)=disabled/", $line, $matches)) {
            $schedulerData[trim($matches[1])] = [
                'active' => false,
                'time' => 'Вимкнено'
            ];
        } elseif (preg_match("/^([^=]+)=(.*)/", $line, $matches)) {
            $schedulerData[trim($matches[1])] = [
                'active' => true,
                'time' => trim($matches[2])
            ];
        }
    }
}

$logData = RegexHelper::analyzeLogFile('log.txt');

class SchedulerPage extends Page
{
    private array $schedulerData;
    private array $logData;
    private int $serverTime;

    public function __construct(
        string $title,
        Translator $t,
        array $schedulerData,
        array $logData
    ) {
        parent::__construct($title, $t);

        $this->schedulerData = $schedulerData;
        $this->logData = $logData;
        $this->serverTime = time();
    }

    private function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function renderLogPanel(): string
    {
        if (empty($this->logData) || ($this->logData['time'] ?? '-') === '-') {
            return '';
        }

        $loginTime = $this->e($this->logData['login_time'] ?? '-');
        $lastTime = $this->e($this->logData['time'] ?? '-');
        $action = $this->e($this->logData['action'] ?? '-');

        return <<<HTML
        <section class="mvc-panel scheduler-log-panel">
            <div class="scheduler-log-grid">
                <div>
                    <span>Останній вхід</span>
                    <strong>{$loginTime}</strong>
                </div>

                <div>
                    <span>Остання дія</span>
                    <strong>{$lastTime}</strong>
                    <p>{$action}</p>
                </div>
            </div>
        </section>
HTML;
    }

    private function renderSchedulerCards(): string
    {
        if (empty($this->schedulerData)) {
            return '<p class="scheduler-empty">Фонові задачі поки не налаштовані.</p>';
        }

        $html = '<div class="scheduler-grid">';

        foreach ($this->schedulerData as $name => $info) {
            $safeName = $this->e($name);
            $isActive = !empty($info['active']);
            $time = $this->e($info['time'] ?? 'Вимкнено');
            $cronDisplayTime = ($time === 'Вимкнено') ? '00:00' : $time;

            $statusClass = $isActive
                ? 'scheduler-status-active'
                : 'scheduler-status-disabled';

            $statusText = $isActive
                ? 'Active'
                : 'Disabled';

            $toggleText = $isActive
                ? 'Вимкнути'
                : 'Увімкнути';

            $activeValue = $isActive ? '1' : '0';

            $html .= <<<HTML
            <article class="scheduler-card">
                <div class="scheduler-card-header">
                    <span class="scheduler-status {$statusClass}">
                        {$statusText}
                    </span>

                    <span 
                        class="scheduler-countdown cron-countdown"
                        data-cron-time="{$time}"
                        data-cron-active="{$activeValue}"
                    >
                        Завантаження...
                    </span>
                </div>

                <form method="POST" action="scheduler.php#scheduler" class="scheduler-rename-form">
                    <input type="hidden" name="action" value="scheduler_rename">
                    <input type="hidden" name="cron_task_name" value="{$safeName}">

                    <input 
                        type="text" 
                        name="new_cron_name" 
                        value="{$safeName}" 
                        required
                    >

                    <button type="submit" title="Змінити системне ім’я">
                        ✎
                    </button>
                </form>

                <div class="scheduler-card-actions">
                    <form method="POST" action="scheduler.php#scheduler">
                        <input type="hidden" name="cron_task_name" value="{$safeName}">
                        <input type="hidden" name="action" value="scheduler_toggle">
                        <input type="hidden" name="cron_is_active" value="{$activeValue}">

                        <button type="submit" class="scheduler-secondary-btn">
                            {$toggleText}
                        </button>
                    </form>

                    <form method="POST" action="scheduler.php#scheduler" class="scheduler-time-form">
                        <input type="hidden" name="action" value="scheduler_schedule">
                        <input type="hidden" name="cron_task_name" value="{$safeName}">

                        <input 
                            type="time" 
                            name="cron_task_time" 
                            value="{$cronDisplayTime}" 
                            required
                        >

                        <button type="submit" class="scheduler-primary-btn">
                            Час
                        </button>
                    </form>
                </div>
            </article>
HTML;
        }

        $html .= '</div>';

        return $html;
    }

    public function renderBody()
    {
        $totalTasks = count($this->schedulerData);
        $activeTasks = count(array_filter(
            $this->schedulerData,
            fn($task) => !empty($task['active'])
        ));
        $disabledTasks = $totalTasks - $activeTasks;

        $logPanelHtml = $this->renderLogPanel();
        $schedulerCardsHtml = $this->renderSchedulerCards();

        echo '<link rel="stylesheet" href="css/scheduler-mvc.css">';

        echo <<<HTML
        <main class="mvc-page scheduler-page" id="scheduler">
            <section class="mvc-shell">
                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">PRO інструменти</div>
                        <h1>Автоматизація ProHour</h1>
                        <p>
                            Керуйте фоновими службами, системними тригерами, резервним копіюванням,
                            розсилками та регламентними операціями.
                        </p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>Активних служб</span>
                        <strong>{$activeTasks}</strong>
                    </div>
                </div>

                <div class="mvc-stats-grid">
                    <div class="mvc-stat-card">
                        <span>Усього служб</span>
                        <strong>{$totalTasks}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Активні</span>
                        <strong>{$activeTasks}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Вимкнені</span>
                        <strong>{$disabledTasks}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Серверний час</span>
                        <strong id="schedulerServerClock">--:--:--</strong>
                    </div>
                </div>

                {$logPanelHtml}

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Фонові служби</h2>
                            <p>
                                Налаштуйте активність, системне ім’я та час запуску автоматизованих задач.
                            </p>
                        </div>
                    </div>

                    {$schedulerCardsHtml}
                </section>
            </section>
        </main>

        <script>
            const serverTimeOnLoad = {$this->serverTime};
            const clientTimeOnLoad = Math.floor(Date.now() / 1000);
            const timeOffset = serverTimeOnLoad - clientTimeOnLoad;

            function formatSeconds(totalSeconds) {
                const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
                const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
                const s = Math.floor(totalSeconds % 60).toString().padStart(2, '0');

                return `\${h}:\${m}:\${s}`;
            }

            function updateSchedulerTimers() {
                const currentServerTime = Math.floor(Date.now() / 1000) + timeOffset;
                const serverClock = document.getElementById('schedulerServerClock');

                if (serverClock) {
                    const now = new Date(currentServerTime * 1000);
                    serverClock.innerHTML = now.toLocaleTimeString('uk-UA');
                }

                document.querySelectorAll('.cron-countdown').forEach(el => {
                    const cronTime = el.dataset.cronTime;
                    const isActive = el.dataset.cronActive === '1';

                    if (!isActive || cronTime === 'Вимкнено' || cronTime === '00:00') {
                        el.innerHTML = 'Зупинено';
                        el.classList.remove('scheduler-countdown-active');
                        return;
                    }

                    const parts = cronTime.split(':');

                    if (parts.length < 2) {
                        el.innerHTML = 'Невідомо';
                        return;
                    }

                    const targetHours = parseInt(parts[0]);
                    const targetMinutes = parseInt(parts[1]);

                    const serverDate = new Date(currentServerTime * 1000);
                    const targetDate = new Date(serverDate.getTime());

                    targetDate.setHours(targetHours, targetMinutes, 0, 0);

                    if (targetDate.getTime() < serverDate.getTime()) {
                        targetDate.setDate(targetDate.getDate() + 1);
                    }

                    const diffSeconds = Math.floor(
                        (targetDate.getTime() - serverDate.getTime()) / 1000
                    );

                    if (diffSeconds <= 0) {
                        el.innerHTML = 'Виконується...';
                        el.classList.add('scheduler-countdown-active');
                        return;
                    }

                    el.innerHTML = '-' + formatSeconds(diffSeconds);
                    el.classList.add('scheduler-countdown-active');
                });
            }

            setInterval(updateSchedulerTimers, 1000);
            updateSchedulerTimers();
        </script>
HTML;
    }
}

$page = new SchedulerPage(
    'Автоматизація | ProHour',
    $translator,
    $schedulerData,
    $logData
);

$page->render();