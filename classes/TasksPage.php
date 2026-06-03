<?php

require_once "Page.php";

class TasksPage extends Page
{
    private $tasks;
    private $serverTime;
    private array $schedulerTasks;

    public function __construct($title, Translator $t, $tasks, array $schedulerTasks = [])
    {
        parent::__construct($title, $t);
        $this->tasks = $tasks;
        $this->serverTime = time(); 
        $this->schedulerTasks = $schedulerTasks;
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
        echo '<section id="scheduler" class="box-panel">';
        echo '<div class="section-badge" style="background: #10b981; color: white;">PRO Інструменти</div>';
        echo '<h2 style="margin-bottom: 10px;">Фонові служби автоматизації</h2>';
        echo '<p style="font-size:15px; color:#64748b; margin-bottom: 30px;">Конфігурація та розклад системних тригерів, автоматичного резервного копіювання бази даних та генерації звітів.</p>';
        
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px; margin-bottom: 10px;">';
        
        foreach ($this->schedulerTasks as $name => $info) {
            $statusBadgeColor = $info['active'] ? 'background: #dcfce7; color: #16a34a;' : 'background: #f1f5f9; color: #475569;';
            $statusText = $info['active'] ? '🟢 Активна' : '⚪ Вимкнена';
            $toggleBtnText = $info['active'] ? 'Вимкнути' : 'Увімкнути';
            $cronDisplayTime = ($info['time'] === 'Вимкнено') ? '00:00' : $info['time'];
            
            echo <<<HTML
            <div class="feature-card" style="display: flex; flex-direction: column; gap: 18px; padding: 30px; transform: none; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); border-radius: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="padding: 6px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; {$statusBadgeColor}">{$statusText}</span>
                    <span class="cron-countdown" data-cron-time="{$info['time']}" data-cron-active="{$info['active']}" style="font-size: 14px; font-weight: 700; color: #ff7f50; font-variant-numeric: tabular-nums;">
                        Завантаження...
                    </span>
                </div>
                
                <form method="POST" action="tasks.php#scheduler" class="edit-form" style="margin: 0; display: flex; gap: 10px; flex-direction: row; align-items: center; width: 100%;">
                    <input type="hidden" name="action" value="scheduler_rename">
                    <input type="hidden" name="cron_task_name" value="{$name}">
                    <input type="text" name="new_cron_name" value="{$name}" required style="padding: 12px 16px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 14px; flex: 1; background: #fff; height: 46px; margin: 0;">
                    <button type="submit" class="control-btn" style="padding: 0; border-radius: 14px; font-size: 14px; margin: 0; width: 46px; height: 46px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-width: 46px;" title="Змінити ідентифікатор служби">✎</button>
                </form>
                
                <div style="display: flex; gap: 12px; margin-top: auto; width: 100%; align-items: center;">
                    <form method="POST" action="tasks.php#scheduler" style="margin: 0; flex: 1;">
                        <input type="hidden" name="cron_task_name" value="{$name}">
                        <input type="hidden" name="action" value="scheduler_toggle">
                        <input type="hidden" name="cron_is_active" value="{$info['active']}">
                        <button type="submit" class="control-btn" style="width: 100%; font-size: 13px; padding: 0 10px; height: 46px; border-radius: 14px; font-weight: 600; background: #f8fafc; color: #475569; margin: 0; display: flex; align-items: center; justify-content: center;">
                            {$toggleBtnText}
                        </button>
                    </form>
                    
                    <form method="POST" action="tasks.php#scheduler" style="margin: 0; display: flex; gap: 8px; flex: 1; align-items: center;">
                        <input type="hidden" name="action" value="scheduler_schedule">
                        <input type="hidden" name="cron_task_name" value="{$name}">
                        <input type="time" name="cron_task_time" value="{$cronDisplayTime}" required style="padding: 10px; width: 90px; border-radius: 14px; border: 1px solid #cbd5e1; font-size: 13px; text-align: center; background: #fff; height: 46px; margin: 0;">
                        <button type="submit" class="control-btn" style="padding: 0 15px; font-size: 13px; border-radius: 14px; background: #ff7f50; color: white; margin: 0; height: 46px; width: auto; font-weight: 600; display: flex; align-items: center; justify-content: center;">Час</button>
                    </form>
                </div>
            </div>
HTML;
        }
        
        echo '</div></section>';
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

        $activeTasks = array_filter($this->tasks, fn($t) => $t['status'] === 'active');
        if (!empty($activeTasks)) {
            echo '<section class="active-dashboard box-panel">';
            echo '<h2>' . $this->t->get('in_progress') . '</h2>';
            echo '<div class="active-grid">';
            foreach ($activeTasks as $task) {
                $currentElapsed = $task['accumulated_time'] + ($this->serverTime - $task['last_started_at']);
                $formattedTime = $this->formatTime($currentElapsed);
                echo <<<HTML
                <div class="active-card">
                    <h3>{$task['name']}</h3>
                    <div class="live-timer" data-start-time="{$task['last_started_at']}" data-acc-time="{$task['accumulated_time']}">
                        {$formattedTime}
                    </div>
                    <form method="POST">
                        <input type="hidden" name="task_id" value="{$task['id']}">
                        <input type="hidden" name="action" value="pause">
                        <button type="submit" class="control-btn pause-btn">{$this->t->get('btn_pause')}</button>
                    </form>
                </div>
HTML;
            }
            echo '</div></section>';
        }

        echo '<section class="tasks-list box-panel">';
        echo '<h2>' . $this->t->get('all_tasks') . '</h2>';
        if (empty($this->tasks)) {
            echo '<p class="empty-msg">' . $this->t->get('empty_list') . '</p>';
        } else {
            foreach ($this->tasks as $task) {
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
                        echo "<form method='POST'><input type='hidden' name='task_id' value='{$task['id']}'><input type='hidden' name='action' value='play'><button type='submit' class='icon-btn' title='{$this->t->get('btn_play')}'>▶</button></form>";
                    } else {
                        echo "<form method='POST'><input type='hidden' name='task_id' value='{$task['id']}'><input type='hidden' name='action' value='pause'><button type='submit' class='icon-btn' title='{$this->t->get('btn_pause')}'>⏸</button></form>";
                    }
                    echo "<form method='POST'><input type='hidden' name='task_id' value='{$task['id']}'><input type='hidden' name='action' value='complete'><button type='submit' class='icon-btn' title='Завершити'>✔</button></form>";
                }
                echo <<<HTML
                        <div class="dropdown">
                            <button class="icon-btn dropbtn">⋮</button>
                            <div class="dropdown-content">
                                <form method="POST" class="edit-form">
                                    <input type="hidden" name="task_id" value="{$task['id']}">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="text" name="new_name" placeholder="{$this->t->get('ph_new_name')}" required>
                                    <button type="submit">{$this->t->get('btn_edit')}</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="{$task['id']}">
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
        
        echo <<<HTML
        <script>
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

                document.querySelectorAll('.cron-countdown').forEach(el => {
                    const cronTime = el.dataset.cronTime;
                    const isActive = el.dataset.cronActive === '1';
                    
                    if (!isActive || cronTime === 'Вимкнено' || cronTime === '00:00') {
                        el.innerHTML = "Зупинено";
                        el.style.color = "#64748b";
                        return;
                    }
                    
                    const parts = cronTime.split(':');
                    if (parts.length < 2) return;
                    
                    const targetHours = parseInt(parts[0]);
                    const targetMinutes = parseInt(parts[1]);
                    
                    const serverDate = new Date(currentServerTime * 1000);
                    const targetDate = new Date(serverDate.getTime());
                    targetDate.setHours(targetHours, targetMinutes, 0, 0);
                    
                    if (targetDate.getTime() < serverDate.getTime()) {
                        targetDate.setDate(targetDate.getDate() + 1);
                    }
                    
                    const diffSeconds = Math.floor((targetDate.getTime() - serverDate.getTime()) / 1000);
                    
                    if (diffSeconds <= 0 || diffSeconds > 86390) {
                        el.innerHTML = "⚡ Виконується...";
                        el.style.color = "#10b981";
                        return;
                    }
                    
                    const h = Math.floor(diffSeconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((diffSeconds % 3600) / 60).toString().padStart(2, '0');
                    const s = Math.floor(diffSeconds % 60).toString().padStart(2, '0');
                    
                    el.innerHTML = `-\${h}:\${m}:\${s}`;
                    el.style.color = "#ff7f50";
                });
            }, 1000);
        </script>
HTML;
    }
}