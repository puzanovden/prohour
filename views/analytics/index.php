<?php

$summary = $summary ?? [];
$byUsers = $byUsers ?? [];
$byProjects = $byProjects ?? [];
$byClients = $byClients ?? [];
$byStatuses = $byStatuses ?? [];
$byPriorities = $byPriorities ?? [];
$timelineEvents = $timelineEvents ?? [];

$workTimeline = $workTimeline ?? [
    'rows' => [],
    'hours' => [],
    'range_start' => time(),
    'range_end' => time() + 3600,
];

$selectedDate = $selectedDate ?? date('Y-m-d');
$prevDate = $prevDate ?? date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = $nextDate ?? date('Y-m-d', strtotime($selectedDate . ' +1 day'));

$currentLang = $_SESSION['lang'] ?? 'uk';
$activeUa = $currentLang === 'uk' ? 'active' : '';
$activeEn = $currentLang === 'en' ? 'active' : '';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function percentWidth(int $value, int $max): int
{
    if ($max <= 0) {
        return 0;
    }

    return max(4, (int)round(($value / $max) * 100));
}

function renderHorizontalBars(array $items, string $emptyText): string
{
    if (empty($items)) {
        return '<p class="analytics-empty">' . e($emptyText) . '</p>';
    }

    $max = 1;

    foreach ($items as $item) {
        $max = max($max, (int)($item['total_seconds'] ?? 0), (int)($item['tasks_count'] ?? 0));
    }

    $html = '<div class="analytics-bars">';

    foreach ($items as $item) {
        $label = e($item['label'] ?? '-');
        $tasksCount = (int)($item['tasks_count'] ?? 0);
        $time = e($item['total_time'] ?? '00:00:00');
        $seconds = (int)($item['total_seconds'] ?? 0);
        $width = percentWidth($seconds > 0 ? $seconds : $tasksCount, $max);

        $html .= <<<HTML
        <div class="analytics-bar-row">
            <div class="analytics-bar-head">
                <strong>{$label}</strong>
                <span>{$tasksCount} задач · {$time}</span>
            </div>
            <div class="analytics-bar-track">
                <div class="analytics-bar-fill" style="width: {$width}%"></div>
            </div>
        </div>
HTML;
    }

    $html .= '</div>';

    return $html;
}

$totalTasks = (int)($summary['total_tasks'] ?? 0);
$activeTasks = (int)($summary['active_tasks'] ?? 0);
$pausedTasks = (int)($summary['paused_tasks'] ?? 0);
$completedTasks = (int)($summary['completed_tasks'] ?? 0);
$totalTime = e($summary['total_time'] ?? '00:00:00');

$userName = e($_SESSION['user_name'] ?? 'User');
$userAvatarPath = $_SESSION['user_avatar'] ?? '';

if (!empty($userAvatarPath)) {
    $safeAvatarPath = e($userAvatarPath);
    $userAvatarHtml = "<img src=\"{$safeAvatarPath}\" alt=\"Аватар\" class=\"mvc-header-avatar-img\">";
} else {
    preg_match('/^./u', $userName, $matches);
    $userLetter = e($matches[0] ?? '?');
    $userAvatarHtml = "<span>{$userLetter}</span>";
}

?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= e($title ?? 'Аналітика | ProHour') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/mvc.css">
    <link rel="stylesheet" href="css/analytics.css">
</head>

<body class="mvc-body">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header class="mvc-header">
        <a href="index.php" class="mvc-logo">
            <img src="img/logo.png" alt="ProHour">
        </a>

        <nav class="mvc-nav">
            <a href="tasks.php">Задачі</a>
            <a href="clients.php">Клієнти</a>
            <a href="projects.php">Проєкти</a>
            <a href="app.php?route=analytics&date=<?= e($selectedDate) ?>" class="active">Аналітика</a>
            <a href="scheduler.php">Автоматизація</a>
            <a href="chat.php">Чат</a>
        </nav>

        <div class="mvc-header-actions">
            <div class="lang-switch mvc-lang-switch">
                <a href="app.php?route=analytics&date=<?= e($selectedDate) ?>&lang=uk" class="lang-btn <?= $activeUa ?>">
                    <img src="img/ua.svg" alt="UA"> UA
                </a>

                <a href="app.php?route=analytics&date=<?= e($selectedDate) ?>&lang=en" class="lang-btn <?= $activeEn ?>">
                    <img src="img/gb.svg" alt="EN"> EN
                </a>
            </div>

            <div class="mvc-user-panel">
                <a href="profile.php"><?= $userName ?></a>
                <a href="profile.php" class="mvc-header-avatar"><?= $userAvatarHtml ?></a>
                <a href="logout.php">Вийти</a>
            </div>
        </div>
    </header>

    <main class="mvc-page analytics-page">
        <section class="mvc-shell">

            <div class="mvc-hero">
                <div>
                    <div class="mvc-eyebrow">Analytics Dashboard</div>
                    <h1>Аналітика ProHour</h1>
                    <p>
                        Огляд задач, часу, користувачів, клієнтів, проєктів і часової активності команди.
                    </p>
                </div>

                <div class="mvc-hero-badge">
                    <span>Загальний час</span>
                    <strong><?= $totalTime ?></strong>
                </div>
            </div>

            <div class="mvc-stats-grid">
                <div class="mvc-stat-card">
                    <span>Усього задач</span>
                    <strong><?= $totalTasks ?></strong>
                </div>

                <div class="mvc-stat-card">
                    <span>Активні</span>
                    <strong><?= $activeTasks ?></strong>
                </div>

                <div class="mvc-stat-card">
                    <span>На паузі</span>
                    <strong><?= $pausedTasks ?></strong>
                </div>

                <div class="mvc-stat-card">
                    <span>Виконано</span>
                    <strong><?= $completedTasks ?></strong>
                </div>
            </div>

            <section class="mvc-panel">
                <div class="mvc-panel-header">
                    <div>
                        <h2>Часова карта роботи</h2>
                        <p>
                            По вертикалі — користувачі, по горизонталі — час.
                            Кожен блок показує, над якою задачею користувач працював і в який проміжок часу.
                        </p>

                        <div class="analytics-period-switcher">
                            <a href="app.php?route=analytics&date=<?= e($prevDate) ?>">← Попередній день</a>

                            <strong><?= e(date('d.m.Y', strtotime($selectedDate))) ?></strong>

                            <a href="app.php?route=analytics&date=<?= e($nextDate) ?>">Наступний день →</a>
                        </div>
                    </div>
                </div>

                <?php if (empty($workTimeline['rows'])): ?>
                    <p class="analytics-empty">
                        Поки немає достатньо подій для побудови часової карти.
                        Запустіть і зупиніть кілька задач, щоб побачити інтервали роботи.
                    </p>
                <?php else: ?>
                    <div class="analytics-gantt">
                        <div class="analytics-gantt-scroll">

                            <div class="analytics-gantt-hours">
                                <div class="analytics-gantt-user-spacer"></div>

                                <div class="analytics-gantt-hour-line">
                                    <?php foreach ($workTimeline['hours'] as $hour): ?>
                                        <span style="left: <?= e($hour['left']) ?>%">
                                            <?= e($hour['label']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php foreach ($workTimeline['rows'] as $row): ?>
                                <div class="analytics-gantt-row">
                                    <div class="analytics-gantt-user">
                                        <?= e($row['user_name']) ?>
                                    </div>

                                    <?php $laneCount = max(1, count($row['lanes'] ?? [])); ?>

                                    <div class="analytics-gantt-track" style="min-height: <?= 20 + ($laneCount * 52) ?>px;">
                                        <?php foreach ($workTimeline['hours'] as $hour): ?>
                                            <span 
                                                class="analytics-gantt-grid-line" 
                                                style="left: <?= e($hour['left']) ?>%"
                                            ></span>
                                        <?php endforeach; ?>

                                        <?php foreach ($row['lanes'] as $laneIndex => $lane): ?>
                                            <div class="analytics-gantt-lane" style="top: <?= 10 + ($laneIndex * 52) ?>px;">
                                                <?php foreach ($lane as $interval): ?>
                                                    <div 
                                                        class="analytics-gantt-bar analytics-gantt-<?= e($interval['finish_type']) ?>"
                                                        style="left: <?= e($interval['left']) ?>%; width: <?= e($interval['width']) ?>%;"
                                                        title="<?= e($interval['task_name']) ?>: <?= e($interval['start_label']) ?> - <?= e($interval['end_label']) ?>, <?= e($interval['duration']) ?>"
                                                    >
                                                        <strong><?= e($interval['task_name']) ?></strong>
                                                        <span><?= e($interval['start_label']) ?>–<?= e($interval['end_label']) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="mvc-panel">
                <div class="mvc-panel-header">
                    <div>
                        <h2>Останні часові події</h2>
                        <p>
                            Хто і коли виконував дії із задачами: запуск, пауза, завершення, створення або редагування.
                        </p>
                    </div>
                </div>

                <?php if (empty($timelineEvents)): ?>
                    <p class="analytics-empty">Подій таймлайну поки немає.</p>
                <?php else: ?>
                    <div class="analytics-timeline">
                        <?php foreach (array_slice(array_reverse($timelineEvents), 0, 14) as $event): ?>
                            <article class="analytics-timeline-item">
                                <div class="analytics-timeline-time">
                                    <strong><?= e(date('H:i', $event['timestamp'])) ?></strong>
                                    <span><?= e(date('d.m.Y', $event['timestamp'])) ?></span>
                                </div>

                                <div class="analytics-timeline-content">
                                    <div>
                                        <strong><?= e($event['user_name']) ?></strong>
                                        <span><?= e($event['description']) ?></span>
                                    </div>

                                    <p><?= e($event['task_name']) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="analytics-grid-2">
                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>По користувачах</h2>
                            <p>Скільки задач і часу припадає на кожного виконавця.</p>
                        </div>
                    </div>

                    <?= renderHorizontalBars($byUsers, 'Немає даних по користувачах.') ?>
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>По проєктах</h2>
                            <p>Навантаження та витрачений час за проєктами.</p>
                        </div>
                    </div>

                    <?= renderHorizontalBars($byProjects, 'Немає даних по проєктах.') ?>
                </section>
            </div>

            <div class="analytics-grid-2">
                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>По клієнтах</h2>
                            <p>Сумарна активність команди для кожного клієнта.</p>
                        </div>
                    </div>

                    <?= renderHorizontalBars($byClients, 'Немає даних по клієнтах.') ?>
                </section>

                <section class="mvc-panel">
                    <div class="mvc-panel-header">
                        <div>
                            <h2>Статуси задач</h2>
                            <p>Розподіл задач за поточним станом.</p>
                        </div>
                    </div>

                    <?= renderHorizontalBars($byStatuses, 'Немає даних по статусах.') ?>
                </section>
            </div>

            <section class="mvc-panel">
                <div class="mvc-panel-header">
                    <div>
                        <h2>Пріоритети задач</h2>
                        <p>Розподіл задач за важливістю: низький, звичайний, високий.</p>
                    </div>
                </div>

                <?= renderHorizontalBars($byPriorities, 'Немає даних по пріоритетах.') ?>
            </section>

        </section>
    </main>
</body>
</html>