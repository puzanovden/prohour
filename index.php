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

$translator = new Translator($currentLang);

$homePage = new HomePage($translator->get('home_title'), $translator);

$homePage->render();