<?php
require_once __DIR__ . '/classes/XmlSeedParser.php';
require_once __DIR__ . '/classes/XmlSeedService.php';
require_once __DIR__ . '/classes/Database.php';

use App\Database\Database;
use App\Xml\XmlSeedParser;

$seedFilePath = __DIR__ . '/data/seed.xml';
$parser = new XmlSeedParser();
$seedData = $parser->parseFile($seedFilePath);

$database = new Database();

?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>XML seed-дані ProHour</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f2f2f2; }
        th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>XML seed-дані ProHour</h1>
    <p>
        Ця сторінка демонструє виконання завдань 1-3 лабораторної роботи:
        створення XML-парсера, реєстрацію обробників тегів і текстового вмісту,
        запуск парсера та формування HTML-таблиці.
    </p>

    <?= XmlSeedParser::renderHtmlTable($seedData); ?>
</body>
</html>