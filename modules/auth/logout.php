<?php
// modules/auth/logout.php
session_start();
require_once __DIR__ . '/../../config/konstanta.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;
