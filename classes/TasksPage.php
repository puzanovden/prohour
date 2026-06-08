<?php

require_once "Page.php";

class TasksPage extends Page
{
    private array $tasks;
    private array $clients;
    private array $projects;
    private array $users;
    private int $serverTime;

    public function __construct(
        $title,
        Translator $t,
        array $tasks,
        array $clients = [],
        array $projects = [],
        array $users = []
    ) {
        parent::__construct($title, $t);

        $this->tasks = $tasks;
        $this->clients = $clients;
        $this->projects = $projects;
        $this->users = $users;
        $this->serverTime = time();
    }

    private function formatTime($seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $h, $m, $s);
    }

    private function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Низький',
            'high' => 'Високий',
            default => 'Звичайний',
        };
    }

    private function getPriorityClass(string $priority): string
    {
        return match ($priority) {
            'low' => 'tasks-priority-low',
            'high' => 'tasks-priority-high',
            default => 'tasks-priority-normal',
        };
    }

    private function renderClientOptions(): string
    {
        $html = '<option value="">Без клієнта</option>';

        foreach ($this->clients as $client) {
            $id = $this->e($client['id'] ?? '');
            $name = $this->e($client['name'] ?? '');

            $html .= "<option value=\"{$id}\">{$name}</option>";
        }

        return $html;
    }

    private function renderProjectOptions(): string
    {
        $html = '<option value="">Без проєкту / створити новий</option>';

        foreach ($this->projects as $project) {
            $id = $this->e($project['id'] ?? '');
            $name = $this->e($project['name'] ?? '');
            $clientId = $this->e($project['client_id'] ?? '');
            $clientName = $this->e($project['client_name'] ?? 'Без клієнта');

            $html .= "<option value=\"{$id}\" data-client-id=\"{$clientId}\">{$name} — {$clientName}</option>";
        }

        return $html;
    }

    private function renderUserOptions(): string
    {
        $html = '';

        foreach ($this->users as $user) {
            $id = $this->e($user['id'] ?? '');
            $name = $this->e($user['name'] ?? '');
            $email = $this->e($user['email'] ?? '');

            $selected = ((int)($user['id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)) ? 'selected' : '';

            $html .= "<option value=\"{$id}\" {$selected}>{$name} ({$email})</option>";
        }

        return $html;
    }

    public function renderBody()
    {
        $totalTasks = count($this->tasks);
        $activeCount = count(array_filter($this->tasks, fn($t) => ($t['status'] ?? '') === 'active'));
        $pausedCount = count(array_filter($this->tasks, fn($t) => ($t['status'] ?? '') === 'paused'));
        $completedCount = count(array_filter($this->tasks, fn($t) => ($t['status'] ?? '') === 'completed'));

        $clientOptions = $this->renderClientOptions();
        $projectOptions = $this->renderProjectOptions();
        $userOptions = $this->renderUserOptions();

        echo '<link rel="stylesheet" href="css/tasks-mvc.css">';

        echo <<<HTML
        <main class="mvc-page tasks-mvc-page">
            <section class="mvc-shell">

                <div class="mvc-hero">
                    <div>
                        <div class="mvc-eyebrow">Task Management</div>
                        <h1>Задачі ProHour</h1>
                        <p>
                            Керуйте задачами у межах клієнтів і проєктів, призначайте виконавців та відстежуйте фактично витрачений час.
                        </p>
                    </div>

                    <div class="mvc-hero-badge">
                        <span>Усього задач</span>
                        <strong>{$totalTasks}</strong>
                    </div>
                </div>

                <div class="mvc-stats-grid">
                    <div class="mvc-stat-card">
                        <span>Усього задач</span>
                        <strong>{$totalTasks}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>В роботі</span>
                        <strong>{$activeCount}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>На паузі</span>
                        <strong>{$pausedCount}</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Виконано</span>
                        <strong>{$completedCount}</strong>
                    </div>
                </div>

                
HTML;

        $activeTasks = array_filter($this->tasks, fn($t) => ($t['status'] ?? '') === 'active');

        if (!empty($activeTasks)) {
            echo <<<HTML
                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Зараз у роботі</h2>
                            <p>Активні задачі, для яких запущено таймер обліку часу.</p>
                        </div>
                    </div>

                    <div class="tasks-active-grid">
HTML;

            foreach ($activeTasks as $task) {
                $taskId = $this->e($task['id'] ?? '');
                $taskName = $this->e($task['name'] ?? '');
                $clientName = $this->e($task['client_name'] ?? 'Без клієнта');
                $projectName = $this->e($task['project_name'] ?? 'Без проєкту');
                $assignedUserName = $this->e($task['assigned_user_name'] ?? 'Не призначено');

                $currentElapsed = (int)($task['accumulated_time'] ?? 0) + ($this->serverTime - (int)($task['last_started_at'] ?? 0));
                $formattedTime = $this->formatTime($currentElapsed);

                $lastStartedAt = $this->e($task['last_started_at'] ?? 0);
                $accumulatedTime = $this->e($task['accumulated_time'] ?? 0);

                echo <<<HTML
                        <article class="tasks-active-card">
                            <div class="tasks-card-meta">
                                <span>{$clientName}</span>
                                <span>{$projectName}</span>
                            </div>

                            <h3>{$taskName}</h3>

                            <p class="tasks-assigned">Виконавець: <strong>{$assignedUserName}</strong></p>

                            <div 
                                class="tasks-live-timer live-timer" 
                                data-start-time="{$lastStartedAt}" 
                                data-acc-time="{$accumulatedTime}"
                            >
                                {$formattedTime}
                            </div>

                            <form method="POST">
                                <input type="hidden" name="task_id" value="{$taskId}">
                                <input type="hidden" name="action" value="pause">
                                <button type="submit" class="tasks-control-btn">Пауза</button>
                            </form>
                        </article>
HTML;
            }

            echo <<<HTML
                    </div>
                </section>
HTML;
        }

        echo <<<HTML
                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Усі задачі</h2>
                            <p>Повний список задач із клієнтами, проєктами, виконавцями та витраченим часом.</p>
                        </div>
                    </div>
HTML;

        if (empty($this->tasks)) {
            echo '<p class="tasks-empty">Задач поки немає. Створіть першу задачу через форму вище.</p>';
        } else {
            echo <<<HTML
                    <div class="mvc-table-wrapper">
                        <table class="mvc-table tasks-table">
                            <thead>
                                <tr>
                                    <th>Задача</th>
                                    <th>Клієнт / Проєкт</th>
                                    <th>Виконавець</th>
                                    <th>Пріоритет / Дедлайн</th>
                                    <th>Час</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
HTML;

            foreach ($this->tasks as $task) {
                $taskId = $this->e($task['id'] ?? '');
                $taskName = $this->e($task['name'] ?? '');
                $comment = $this->e($task['comment'] ?? '');

                $clientName = $this->e($task['client_name'] ?? 'Без клієнта');
                $projectName = $this->e($task['project_name'] ?? 'Без проєкту');
                $assignedUserName = $this->e($task['assigned_user_name'] ?? 'Не призначено');

                $status = $task['status'] ?? 'paused';
                $statusText = $this->t->get('status_' . $status);
                $statusClass = 'mvc-status-' . $this->e($status);

                $priority = $task['priority'] ?? 'normal';
                $priorityLabel = $this->getPriorityLabel($priority);
                $priorityClass = $this->getPriorityClass($priority);

                $deadline = !empty($task['deadline'])
                    ? $this->e($task['deadline'])
                    : 'Без дедлайну';

                $elapsed = (int)($task['accumulated_time'] ?? 0);

                if ($status === 'active') {
                    $elapsed += ($this->serverTime - (int)($task['last_started_at'] ?? 0));
                }

                $formattedTime = $this->formatTime($elapsed);

                $lastStartedAt = $this->e($task['last_started_at'] ?? 0);
                $accumulatedTime = $this->e($task['accumulated_time'] ?? 0);

                $timerHtml = $status === 'active'
                    ? "<div class=\"tasks-table-timer live-timer\" data-start-time=\"{$lastStartedAt}\" data-acc-time=\"{$accumulatedTime}\">{$formattedTime}</div>"
                    : "<div class=\"tasks-table-timer\">{$formattedTime}</div>";

                $commentHtml = !empty($comment)
                    ? "<span class=\"tasks-comment\">{$comment}</span>"
                    : "";

                echo <<<HTML
                                <tr>
                                    <td>
                                        <strong>{$taskName}</strong>
                                        <span class="mvc-status-pill {$statusClass}">{$statusText}</span>
                                        {$commentHtml}
                                    </td>

                                    <td>
                                        <strong>{$clientName}</strong>
                                        <span class="tasks-subtext">{$projectName}</span>
                                    </td>

                                    <td>{$assignedUserName}</td>

                                    <td>
                                        <span class="tasks-priority {$priorityClass}">{$priorityLabel}</span>
                                        <span class="tasks-subtext">{$deadline}</span>
                                    </td>

                                    <td>{$timerHtml}</td>

                                    <td>
                                        <div class="tasks-actions">
HTML;

                if ($status !== 'completed') {
                    if ($status === 'paused') {
                        echo <<<HTML
                                            <form method="POST">
                                                <input type="hidden" name="task_id" value="{$taskId}">
                                                <input type="hidden" name="action" value="play">
                                                <button type="submit" class="tasks-icon-btn" title="Запустити">▶</button>
                                            </form>
HTML;
                    } else {
                        echo <<<HTML
                                            <form method="POST">
                                                <input type="hidden" name="task_id" value="{$taskId}">
                                                <input type="hidden" name="action" value="pause">
                                                <button type="submit" class="tasks-icon-btn" title="Пауза">⏸</button>
                                            </form>
HTML;
                    }

                    echo <<<HTML
                                            <form method="POST">
                                                <input type="hidden" name="task_id" value="{$taskId}">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="tasks-icon-btn" title="Завершити">✔</button>
                                            </form>
HTML;
                }

                echo <<<HTML
                                            <details class="tasks-more">
                                                <summary>⋮</summary>

                                                <div class="tasks-more-menu">
                                                    <form method="POST" class="tasks-edit-form">
                                                        <input type="hidden" name="task_id" value="{$taskId}">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="text" name="new_name" placeholder="Нова назва" required>
                                                        <button type="submit">Змінити</button>
                                                    </form>

                                                    <form method="POST">
                                                        <input type="hidden" name="task_id" value="{$taskId}">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="tasks-delete-btn">Видалити</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                </tr>
HTML;
            }

            

            echo <<<HTML
                            </tbody>
                        </table>
                    </div>
HTML;
        }

        echo <<<HTML
                </section>
            </section>

            <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Нова задача</h2>
                            <p>Створіть задачу, прив’яжіть її до клієнта та проєкту або створіть новий проєкт одразу з форми.</p>
                        </div>
                    </div>

                    <form method="POST" class="mvc-form tasks-create-form">
                        <input type="hidden" name="action" value="create">

                        <label class="tasks-form-wide">
                            Назва задачі
                            <input 
                                type="text" 
                                name="task_name" 
                                placeholder="Наприклад, Підготувати звіт для клієнта" 
                                required
                            >
                        </label>

                        <label>
                            Клієнт
                            <select name="client_id" id="clientSelect">
                                {$clientOptions}
                            </select>
                        </label>

                        <label>
                            Існуючий проєкт
                            <select name="project_id" id="projectSelect">
                                {$projectOptions}
                            </select>
                        </label>

                        <label>
                            Новий проєкт
                            <input 
                                type="text" 
                                name="new_project_name" 
                                id="newProjectInput"
                                placeholder="Якщо проєкту ще немає"
                            >
                        </label>

                        <label>
                            Виконавець
                            <select name="assigned_to_user_id" required>
                                {$userOptions}
                            </select>
                        </label>

                        <label>
                            Пріоритет
                            <select name="priority">
                                <option value="normal">Звичайний</option>
                                <option value="high">Високий</option>
                                <option value="low">Низький</option>
                            </select>
                        </label>

                        <label>
                            Дедлайн
                            <input type="date" name="deadline">
                        </label>

                        <label class="tasks-form-wide">
                            Коментар
                            <textarea name="comment" placeholder="Додатковий опис задачі, вимоги або домовленості"></textarea>
                        </label>

                        <button type="submit">Створити задачу</button>
                    </form>
                </section>
        </main>

        <script>
            const serverTimeOnLoad = {$this->serverTime};
            const clientTimeOnLoad = Math.floor(Date.now() / 1000);
            const timeOffset = serverTimeOnLoad - clientTimeOnLoad;

            function updateLiveTimers() {
                const currentServerTime = Math.floor(Date.now() / 1000) + timeOffset;

                document.querySelectorAll('.live-timer').forEach(el => {
                    const startAt = parseInt(el.dataset.startTime);
                    const accTime = parseFloat(el.dataset.accTime);

                    if (startAt) {
                        const totalSeconds = accTime + (currentServerTime - startAt);
                        const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
                        const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
                        const s = Math.floor(totalSeconds % 60).toString().padStart(2, '0');

                        el.innerHTML = `\${h}:\${m}:\${s}`;
                    }
                });
            }

            setInterval(updateLiveTimers, 1000);
            updateLiveTimers();

            const clientSelect = document.getElementById('clientSelect');
            const projectSelect = document.getElementById('projectSelect');
            const newProjectInput = document.getElementById('newProjectInput');

            function filterProjectsByClient() {
                if (!clientSelect || !projectSelect) {
                    return;
                }

                const selectedClientId = clientSelect.value;

                Array.from(projectSelect.options).forEach(option => {
                    const optionClientId = option.dataset.clientId || '';

                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = selectedClientId && optionClientId !== selectedClientId;
                });

                const selectedOption = projectSelect.options[projectSelect.selectedIndex];

                if (selectedOption && selectedOption.hidden) {
                    projectSelect.value = '';
                }
            }

            if (clientSelect) {
                clientSelect.addEventListener('change', filterProjectsByClient);
                filterProjectsByClient();
            }

            if (newProjectInput && projectSelect) {
                newProjectInput.addEventListener('input', () => {
                    if (newProjectInput.value.trim().length > 0) {
                        projectSelect.value = '';
                    }
                });

                projectSelect.addEventListener('change', () => {
                    if (projectSelect.value) {
                        newProjectInput.value = '';
                    }
                });
            }
        </script>
HTML;
    }
}