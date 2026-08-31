<?php
// modules/laporan/cetak_pdf.php
//
// FITUR TAMBAHAN (nilai plus): cetak laporan transaksi ke PDF.
// Menggunakan library FPDF (ringan, gratis, tanpa dependency berat).
//
// CARA PASANG (pilih salah satu):
//   1) Composer:  composer require setasign/fpdf
//      lalu ganti require di bawah menjadi: require __DIR__ . '/../../vendor/autoload.php';
//   2) Manual: unduh dari http://www.fpdf.org/, taruh folder "fpdf" di dalam vendor/,
//      sehingga file utamanya ada di vendor/fpdf/fpdf.php (sesuai require di bawah).

require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$fpdf_path = __DIR__ . '/../../vendor/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    die(
        'Library FPDF belum terpasang. Unduh dari http://www.fpdf.org/ ' .
        'dan taruh di folder vendor/fpdf/ (lihat komentar di bagian atas file ini), ' .
        'atau jalankan: composer require setasign/fpdf'
    );
}
require_once $fpdf_path;

// Ambil data transaksi 30 hari terakhir untuk laporan
$transaksi = $pdo->query(
    "SELECT t.kode_transaksi, p.nama_pelanggan, t.tanggal_sewa, t.tanggal_rencana_kembali,
            t.status, t.total_biaya
     FROM tb_transaksi_sewa t
     JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
     WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     ORDER BY t.created_at DESC"
)->fetchAll();

class LaporanPDF extends FPDF
{
    public function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'Laporan Transaksi Penyewaan Alat', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, 'Periode: 30 hari terakhir - Dicetak: ' . date('d-m-Y H:i'), 0, 1, 'C');
        $this->Ln(4);

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(30, 7, 'Kode', 1, 0, 'C', true);
        $this->Cell(45, 7, 'Pelanggan', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Tgl Sewa', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Rencana Kembali', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Status', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Total Biaya', 1, 1, 'C', true);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new LaporanPDF();
$pdf->AliasNbPages();
$pdf->AddPage('L'); // landscape agar kolom muat
$pdf->SetFont('Arial', '', 9);

foreach ($transaksi as $t) {
    $pdf->Cell(30, 6, $t['kode_transaksi'], 1);
    $pdf->Cell(45, 6, $t['nama_pelanggan'], 1);
    $pdf->Cell(25, 6, date('d-m-Y', strtotime($t['tanggal_sewa'])), 1);
    $pdf->Cell(30, 6, date('d-m-Y', strtotime($t['tanggal_rencana_kembali'])), 1);
    $pdf->Cell(25, 6, ucfirst($t['status']), 1);
    $pdf->Cell(35, 6, 'Rp ' . number_format($t['total_biaya'], 0, ',', '.'), 1, 1, 'R');
}

if (empty($transaksi)) {
    $pdf->Cell(0, 10, 'Tidak ada transaksi dalam periode ini.', 1, 1, 'C');
}

$pdf->Output('I', 'laporan_transaksi_' . date('Ymd') . '.pdf');
