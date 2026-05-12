<?php

require_once "Page.php";

class TasksPage extends Page
{
    private $tasks;
    private $serverTime;

    public function __construct($title, Translator $t, $tasks)
    {
        parent::__construct($title, $t);
        $this->tasks = $tasks;
        $this->serverTime = time(); 
    }

    private function formatTime($seconds)
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf("%02d:%02d:%02d", $h, $m, $s);
    }

    public function renderBody()
    {
        echo '<main class="tasks-main">';
        
        // Форма додавання задачі
        echo <<<HTML
        <section class="task-creator box-panel">
            <h2>{$this->t->get('add_task')}</h2>
            <form method="POST" class="create-form">
                <input type="hidden" name="action" value="create">
                <input type="text" name="task_name" placeholder="{$this->t->get('task_name_ph')}" required>
                <button type="submit" class="primary-btn">{$this->t->get('btn_create')}</button>
            </form>
        </section>
HTML;

        // Дашборд активних задач
        $activeTasks = array_filter($this->tasks, fn($t) => $t['status'] === 'active');
        
        if (!empty($activeTasks)) {
            echo '<section class="active-dashboard box-panel">';
            echo '<h2>' . $this->t->get('in_progress') . '</h2>';
            echo '<div class="active-grid">';
            foreach ($activeTasks as $id => $task) {
                $currentElapsed = $task['accumulated_time'] + ($this->serverTime - $task['last_started_at']);
                $formattedTime = $this->formatTime($currentElapsed);
                
                echo <<<HTML
                <div class="active-card">
                    <h3>{$task['name']}</h3>
                    <div class="live-timer" data-start-time="{$task['last_started_at']}" data-acc-time="{$task['accumulated_time']}">
                        {$formattedTime}
                    </div>
                    <form method="POST">
                        <input type="hidden" name="task_id" value="{$id}">
                        <input type="hidden" name="action" value="pause">
                        <button type="submit" class="control-btn pause-btn">{$this->t->get('btn_pause')}</button>
                    </form>
                </div>
HTML;
            }
            echo '</div></section>';
        }

        // Загальний список задач
        echo '<section class="tasks-list box-panel">';
        echo '<h2>' . $this->t->get('all_tasks') . '</h2>';
        
        if (empty($this->tasks)) {
            echo '<p class="empty-msg">' . $this->t->get('empty_list') . '</p>';
        } else {
            foreach ($this->tasks as $id => $task) {
                $elapsed = $task['accumulated_time'];
                if ($task['status'] === 'active') {
                    $elapsed += ($this->serverTime - $task['last_started_at']);
                }
                $formattedTime = $this->formatTime($elapsed);
                
                $statusClass = "status-" . $task['status'];
                $statusText = $this->t->get('status_' . $task['status']);

                echo <<<HTML
                <div class="task-row {$statusClass}">
                    <div class="task-info">
                        <span class="task-status-badge">{$statusText}</span>
                        <span class="task-name">{$task['name']}</span>
                    </div>
                    
                    <div class="task-actions">
                        <div class="task-timer">{$formattedTime}</div>
HTML;

                if ($task['status'] !== 'completed') {
                    if ($task['status'] === 'paused') {
                        echo "<form method='POST'><input type='hidden' name='task_id' value='{$id}'><input type='hidden' name='action' value='play'><button type='submit' class='icon-btn' title='{$this->t->get('btn_play')}'>▶</button></form>";
                    } else {
                        echo "<form method='POST'><input type='hidden' name='task_id' value='{$id}'><input type='hidden' name='action' value='pause'><button type='submit' class='icon-btn' title='{$this->t->get('btn_pause')}'>⏸</button></form>";
                    }
                    echo "<form method='POST'><input type='hidden' name='task_id' value='{$id}'><input type='hidden' name='action' value='complete'><button type='submit' class='icon-btn' title='Завершити'>✔</button></form>";
                }

                echo <<<HTML
                        <div class="dropdown">
                            <button class="icon-btn dropbtn">⋮</button>
                            <div class="dropdown-content">
                                <form method="POST" class="edit-form">
                                    <input type="hidden" name="task_id" value="{$id}">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="text" name="new_name" placeholder="{$this->t->get('ph_new_name')}" required>
                                    <button type="submit">{$this->t->get('btn_edit')}</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="{$id}">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="danger-txt">{$this->t->get('btn_delete')}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
HTML;
            }
        }
        echo '</section></main>';
        

        // JS для живого таймера
        echo <<<HTML
        <script>
            // Синхронізація часу між клієнтом і сервером
            const serverTimeOnLoad = {$this->serverTime};
            const clientTimeOnLoad = Math.floor(Date.now() / 1000);
            const timeOffset = serverTimeOnLoad - clientTimeOnLoad;

            setInterval(() => {
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
            }, 1000);
        </script>
HTML;
    }
}