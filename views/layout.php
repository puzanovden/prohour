<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'ProHour', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/mvc.css">
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
            <a href="app.php?route=analytics">Аналітика</a>
            <a href="app.php?route=tasks/create">Нова задача</a>
            <a href="chat.php">Чат</a>
        </nav>

        <div class="mvc-user-panel">
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
            <a href="logout.php">Вийти</a>
        </div>
    </header>

    <main class="mvc-page">
        <section class="mvc-shell">
            <?php require __DIR__ . '/' . $template . '.php'; ?>
        </section>
    </main>
</body>
</html>