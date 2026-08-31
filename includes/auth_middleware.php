<?php
// includes/auth_middleware.php
// Panggil file ini di baris paling atas setiap halaman yang butuh proteksi login/role.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/konstanta.php';

// Memastikan user sudah login. Jika belum, arahkan ke halaman login.
function cek_login() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

// Memastikan user sudah login DAN rolenya termasuk dalam daftar yang diizinkan.
// Contoh pemakaian: cek_role(['admin']); atau cek_role(['admin', 'petugas']);
function cek_role(array $role_diizinkan) {
    cek_login();
    if (!in_array($_SESSION['nama_role'], $role_diizinkan, true)) {
        http_response_code(403);
        die('Akses ditolak. Anda tidak memiliki hak akses ke halaman ini.');
    }
}
