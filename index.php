<?php
// index.php - entry point aplikasi
session_start();
require_once __DIR__ . '/config/konstanta.php';

if (isset($_SESSION['id_user'])) {
    header('Location: ' . BASE_URL . '/modules/laporan/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
}
exit;
