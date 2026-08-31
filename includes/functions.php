<?php
// includes/functions.php
// Kumpulan fungsi bantu yang dipakai di berbagai modul.

function format_rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function format_tanggal($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

// Membuat kode unik untuk transaksi/pengembalian, contoh: TRX-20260831-A1B2
function generate_kode($prefix) {
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}

// Membersihkan input teks sederhana sebelum ditampilkan (mencegah XSS saat output ke HTML)
function h($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

// Menghitung selisih hari terlambat antara tanggal rencana kembali dan tanggal aktual
function hitung_hari_terlambat($tanggal_rencana, $tanggal_aktual) {
    $rencana = new DateTime($tanggal_rencana);
    $aktual  = new DateTime($tanggal_aktual);
    if ($aktual <= $rencana) {
        return 0;
    }
    return (int) $rencana->diff($aktual)->days;
}
