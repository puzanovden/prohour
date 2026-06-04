<?php
function formatSecondsForMvc(int $seconds): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
}

$totalTasks = count($tasks);
$activeTasks = 0;
$pausedTasks = 0;
$completedTasks = 0;

foreach ($tasks as $task) {
    if (($task['status'] ?? '') === 'active') {
        $activeTasks++;
    } elseif (($task['status'] ?? '') === 'paused') {
        $pausedTasks++;
    } elseif (($task['status'] ?? '') === 'completed') {
        $completedTasks++;
    }
}
?>

<div class="mvc-hero">
    <div>
        <div class="mvc-eyebrow">Лабораторна робота №5</div>
        <h1>Аналітика часу ProHour</h1>
        <p>
            Переглядайте, скільки задач зараз у роботі, скільки вже завершено
            та як розподіляється витрачений час між ними.
        </p>
    </div>

    <div class="mvc-hero-badge">
        <span>Джерело даних</span>
        <strong><?= htmlspecialchars($adapterName, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
</div>

<div class="mvc-stats-grid">
    <article class="mvc-stat-card">
        <span>Усього задач</span>
        <strong><?= $totalTasks ?></strong>
    </article>

    <article class="mvc-stat-card">
        <span>В роботі</span>
        <strong><?= $activeTasks ?></strong>
    </article>

    <article class="mvc-stat-card">
        <span>На паузі</span>
        <strong><?= $pausedTasks ?></strong>
    </article>

    <article class="mvc-stat-card">
        <span>Виконано</span>
        <strong><?= $completedTasks ?></strong>
    </article>
</div>

<section class="mvc-panel">
    <div class="mvc-panel-header">
        <div>
            <h2>Фокус аналітики</h2>
            <p>
                Поточний режим перегляду:
                <strong><?= htmlspecialchars($strategyName, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
    </div>

    <form method="GET" class="mvc-strategy-form">
        <input type="hidden" name="route" value="analytics">

        <label>
            Показати дані
            <select name="strategy">
                <option value="simple" <?= $selectedStrategy === 'simple' ? 'selected' : '' ?>>
                    Загальна картина по часу
                </option>
                <option value="status" <?= $selectedStrategy === 'status' ? 'selected' : '' ?>>
                    Розподіл часу за статусами
                </option>
            </select>
        </label>

        <button type="submit">Оновити перегляд</button>
    </form>
</section>

<?php if (($analytics['type'] ?? '') === 'simple'): ?>
    <section class="mvc-panel mvc-result-panel">
        <div class="mvc-panel-header">
            <div>
                <h2>Загальна картина</h2>
                <p>
                    Сумарний час показує, скільки роботи вже зафіксовано у задачах.
                    Це допомагає швидко оцінити загальне навантаження.
                </p>
            </div>
        </div>

        <div class="mvc-big-metric">
            <span>Загальний витрачений час</span>
            <strong><?= formatSecondsForMvc((int)$analytics['total_seconds']) ?></strong>
        </div>
    </section>
<?php endif; ?>

<?php if (($analytics['type'] ?? '') === 'status_grouped'): ?>
    <section class="mvc-panel">
        <div class="mvc-panel-header">
            <div>
                <h2>Час за статусами</h2>
                <p>
                    Такий перегляд показує, де саме накопичується час:
                    у поточній роботі, завершених задачах або відкладених активностях.
                </p>
            </div>
        </div>

        <div class="mvc-status-grid">
            <?php foreach ($analytics['groups'] as $status => $seconds): ?>
                <article class="mvc-status-card">
                    <span><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= formatSecondsForMvc((int)$seconds) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mvc-panel">
    <div class="mvc-panel-header">
        <div>
            <h2>Список задач</h2>
            <p>
                Тут зібрані задачі разом із відповідальними користувачами,
                проєктами та клієнтами, щоб легше бачити контекст виконаної роботи.
            </p>
        </div>

        <a href="app.php?route=tasks/create" class="mvc-secondary-link">+ Створити задачу</a>
    </div>

    <div class="mvc-table-wrapper">
        <table class="mvc-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Назва</th>
                    <th>Статус</th>
                    <th>Користувач</th>
                    <th>Проєкт</th>
                    <th>Клієнт</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($task['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <td>
                            <span class="mvc-status-pill mvc-status-<?= htmlspecialchars($task['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($task['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($task['user_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($task['project_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($task['client_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>