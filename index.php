<?php
session_start();


if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: index.php");
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'uk';

require_once "classes/Translator.php";
require_once "classes/HomePage.php";
require_once "classes/Database.php";
require_once "classes/TaskRepository.php";

$translator = new Translator($currentLang);

$db = new Database();

$db->connect();

$db->createTables();

$taskRepository = new TaskRepository(
    $db->getConnection()
);

$tasks = $taskRepository->getTasks();

$homePage = new HomePage($translator->get('home_title'), $translator);

$homePage->render();