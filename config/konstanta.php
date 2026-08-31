<?php
// config/konstanta.php
// Konstanta global aplikasi. Sesuaikan BASE_URL dengan folder project Anda di server.

define('APP_NAME', 'Sistem Informasi Penyewaan Alat');
define('BASE_URL', 'http://localhost/sistem-penyewaan-alat');

// Tarif denda keterlambatan: persentase dari harga sewa/hari per alat, per hari terlambat.
// Contoh: 0.5 artinya denda = 50% x harga_sewa_per_hari x jumlah_alat x hari_terlambat
define('PERSEN_DENDA_PER_HARI', 0.5);
