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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/xml-seed.css">
</head>
<body>
    <h1>XML seed-дані ProHour</h1>
    <?= XmlSeedParser::renderHtmlTable($seedData); ?>
</body>
</html>