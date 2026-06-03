<?php
session_start();

require_once "classes/AuthService.php";

use App\Services\AuthService;

AuthService::logout();

header("Location: index.php");
exit;