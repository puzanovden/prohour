<?php

session_start();
date_default_timezone_set('Europe/Kyiv');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "classes/TaskActionDomLogger.php";

use App\Services\TaskActionDomLogger;

$logger = new TaskActionDomLogger(__DIR__ . '/data/task-actions.xml');
$actions = $logger->getActions();

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>XML-журнал дій | ProHour</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/task-actions.css">
</head>
<body>
    <main class="page-card">
        <a href="tasks.php" class="back-link">← Назад до задач</a>

        <h1>XML-журнал дій</h1>

        <?php if (empty($actions)): ?>
            <div class="empty-message">
                XML-журнал поки порожній. Виконайте дію на сторінці задач.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Тип</th>
                        <th>Опис</th>
                        <th>Користувач</th>
                        <th>Email</th>
                        <th>ID задачі</th>
                        <th>Фонова задача</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actions as $action): ?>
                        <tr>
                            <td><?= htmlspecialchars($action['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['user_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['user_email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['task_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($action['scheduler_task'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>