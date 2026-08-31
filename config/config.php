<?php
// config/database.php
// Koneksi database menggunakan PDO + prepared statement asli (mencegah SQL Injection).

$host   = 'localhost';
$dbname = 'sistem_penyewaan_alat';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // WAJIB false: memaksa MySQL menjalankan prepared statement asli,
            // bukan hanya emulasi di sisi PHP.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Jangan tampilkan detail error database ke user di lingkungan produksi.
    die('Koneksi database gagal. Silakan hubungi administrator.');
}
