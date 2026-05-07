<?php
session_start();

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];
$time = date("Y-m-d H:i:s");

$log = "$time | IP: $ip | Browser: $agent\n";
file_put_contents("log.txt", $log, FILE_APPEND);

// COOKIE
if (isset($_COOKIE['visits'])) {
    $visits = $_COOKIE['visits'] + 1;
} else {
    $visits = 1;
}
setcookie("visits", $visits, time() + 3600 * 24 * 30);

// FILE COUNTER
$file = "counter.txt";
if (!file_exists($file)) file_put_contents($file, 0);

if (!isset($_SESSION['counted'])) {
    $count = file_get_contents($file) + 1;
    file_put_contents($file, $count);
    $_SESSION['counted'] = true;
} else {
    $count = file_get_contents($file);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>ProHour</title>
    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #0f172a;
            color: #e2e8f0;
        }

        header {
            background: #1e293b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #38bdf8;
        }

        .container {
            padding: 40px;
        }

        .cards {
            display: flex;
            gap: 20px;
        }

        .card {
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
            width: 250px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #94a3b8;
        }

        .value {
            font-size: 28px;
            font-weight: bold;
        }

        .big {
            margin-top: 30px;
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
        }

        button {
            margin-top: 15px;
            padding: 10px 15px;
            background: #38bdf8;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #0ea5e9;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">ProHour</div>
    <div>Dashboard</div>
</header>

<div class="container">

    <h2>Аналітика системи</h2>

    <div class="cards">
        <div class="card">
            <h3>Загальні відвідування</h3>
            <div class="value"><?= $count ?></div>
        </div>

        <div class="card">
            <h3>Ваші відвідування</h3>
            <div class="value"><?= $visits ?></div>
        </div>
    </div>

    <div class="big">
        <h3>Про систему</h3>
        <p>
            ProHour — система обліку робочого часу співробітників з можливістю аналізу ефективності,
            контролю задач та формування звітності.
        </p>

        <button onclick="location.reload()">Оновити</button>
    </div>

</div>

</body>
</html>