<?php

require_once "Page.php";

class SchedulerPage extends Page
{
    private array $schedulerTasks;
    private array $logData;

    public function __construct($title, Translator $t, array $schedulerTasks = [], array $logData = [])
    {
        parent::__construct($title, $t);

        $this->schedulerTasks = $schedulerTasks;
        $this->logData = $logData;
    }

    public function renderBody()
    {
        echo '<main class="tasks-main">';
        echo '<section id="scheduler" class="box-panel">';

        echo '<div style="display: flex; justify-content: flex-start; align-items: flex-start; flex-wrap: wrap; gap: 40px; margin-bottom: 30px;">';
        echo '  <div style="flex: 1; min-width: 280px;">';
        echo '      <div class="section-badge" style="background: #10b981; color: white;">PRO Інструменти</div>';
        echo '      <h2 style="margin-bottom: 10px;">Фонові служби автоматизації</h2>';
        echo '      <p style="font-size:15px; color:#64748b; margin: 0;">Конфігурація та розклад системних тригерів, автоматичного резервного копіювання бази даних та генерації звітів.</p>';
        echo '  </div>';

        if (!empty($this->logData) && $this->logData['time'] !== '-') {
            echo '  <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 20px; font-size: 13px; color: #475569; min-width: 320px; line-height: 1.6; box-shadow: 0 4px 12px rgba(0,0,0,0.01); margin-top: 10px;">';
            echo '      <span style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 5px; font-size: 14px;">🔑 Моніторинг доступу (Cron)</span>';
            echo '      • Останній вхід: <span style="color: #0f172a; font-weight: 600;">' . htmlspecialchars($this->logData['login_time']) . '</span><br>';
            echo '      • Остання дія: <span style="color: #0f172a; font-weight: 600;">' . htmlspecialchars($this->logData['time']) . '</span> <span style="font-size: 11px; color: #94a3b8;">(' . htmlspecialchars($this->logData['action']) . ')</span>';
            echo '  </div>';
        }

        echo '</div>';

        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px; margin-bottom: 10px;">';

        foreach ($this->schedulerTasks as $name => $info) {
            $statusBadgeColor = $info['active'] ? 'background: #dcfce7; color: #16a34a;' : 'background: #f1f5f9; color: #475569;';
            $statusText = $info['active'] ? '🟢 Active' : '⚪ Disabled';
            $toggleBtnText = $info['active'] ? 'Вимкнути' : 'Увімкнути';
            $cronDisplayTime = ($info['time'] === 'Вимкнено') ? '00:00' : $info['time'];

            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safeTime = htmlspecialchars($info['time'], ENT_QUOTES, 'UTF-8');
            $safeActive = $info['active'] ? '1' : '0';
            $safeCronDisplayTime = htmlspecialchars($cronDisplayTime, ENT_QUOTES, 'UTF-8');

            echo <<<HTML
            <div class="feature-card" style="display: flex; flex-direction: column; gap: 18px; padding: 30px; transform: none; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); border-radius: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="padding: 6px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; {$statusBadgeColor}">{$statusText}</span>
                    <span class="cron-countdown" data-cron-time="{$safeTime}" data-cron-active="{$safeActive}" style="font-size: 14px; font-weight: 700; color: #ff7f50; font-variant-numeric: tabular-nums;">
                        Завантаження...
                    </span>
                </div>

                <form method="POST" action="scheduler.php#scheduler" class="edit-form" style="margin: 0; display: flex; gap: 10px; flex-direction: row; align-items: center; width: 100%;">
                    <input type="hidden" name="action" value="scheduler_rename">
                    <input type="hidden" name="cron_task_name" value="{$safeName}">
                    <input type="text" name="new_cron_name" value="{$safeName}" required style="padding: 12px 16px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 14px; flex: 1; background: #fff; height: 46px; margin: 0;">
                    <button type="submit" class="control-btn" style="padding: 0; border-radius: 14px; font-size: 14px; margin: 0; width: 46px; height: 46px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-width: 46px;" title="Змінити ідентифікатор служби">✎</button>
                </form>

                <div style="display: flex; gap: 12px; margin-top: auto; width: 100%; align-items: center;">
                    <form method="POST" action="scheduler.php#scheduler" style="margin: 0; flex: 1;">
                        <input type="hidden" name="cron_task_name" value="{$safeName}">
                        <input type="hidden" name="action" value="scheduler_toggle">
                        <input type="hidden" name="cron_is_active" value="{$safeActive}">
                        <button type="submit" class="control-btn" style="width: 100%; font-size: 13px; padding: 0 10px; height: 46px; border-radius: 14px; font-weight: 600; background: #f8fafc; color: #475569; margin: 0; display: flex; align-items: center; justify-content: center;">
                            {$toggleBtnText}
                        </button>
                    </form>

                    <form method="POST" action="scheduler.php#scheduler" style="margin: 0; display: flex; gap: 8px; flex: 1; align-items: center;">
                        <input type="hidden" name="action" value="scheduler_schedule">
                        <input type="hidden" name="cron_task_name" value="{$safeName}">
                        <input type="time" name="cron_task_time" value="{$safeCronDisplayTime}" required style="padding: 10px; width: 90px; border-radius: 14px; border: 1px solid #cbd5e1; font-size: 13px; text-align: center; background: #fff; height: 46px; margin: 0;">
                        <button type="submit" class="control-btn" style="padding: 0 15px; font-size: 13px; border-radius: 14px; background: #ff7f50; color: white; margin: 0; height: 46px; width: auto; font-weight: 600; display: flex; align-items: center; justify-content: center;">Час</button>
                    </form>
                </div>
            </div>
HTML;
        }

        echo '</div></section></main>';

        echo <<<HTML
        <script>
            setInterval(() => {
                const currentServerTime = Math.floor(Date.now() / 1000);

                document.querySelectorAll('.cron-countdown').forEach(el => {
                    const cronTime = el.dataset.cronTime;
                    const isActive = el.dataset.cronActive === '1';

                    if (!isActive || cronTime === 'Вимкнено' || cronTime === '00:00') {
                        el.innerHTML = "Зупинено";
                        el.style.color = "#64748b";
                        return;
                    }

                    const parts = cronTime.split(':');

                    if (parts.length < 2) {
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