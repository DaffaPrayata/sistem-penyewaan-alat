<?php
// modules/laporan/dashboard.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

// --- Ringkasan angka ---
$total_alat        = $pdo->query("SELECT COUNT(*) c FROM tb_alat WHERE status='aktif'")->fetch()['c'];
$stok_tersedia     = $pdo->query("SELECT COALESCE(SUM(stok_tersedia),0) c FROM tb_alat")->fetch()['c'];
$transaksi_berjalan = $pdo->query("SELECT COUNT(*) c FROM tb_transaksi_sewa WHERE status='berjalan'")->fetch()['c'];
$transaksi_terlambat = $pdo->query(
    "SELECT COUNT(*) c FROM tb_transaksi_sewa WHERE status='berjalan' AND tanggal_rencana_kembali < CURDATE()"
)->fetch()['c'];
$pendapatan_bulan_ini = $pdo->query(
    "SELECT COALESCE(SUM(total_pembayaran),0) c FROM tb_pengembalian
     WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
)->fetch()['c'];

// --- Transaksi yang perlu perhatian (berjalan, urut yang paling mendekati/terlewat jatuh tempo) ---
$transaksi_perhatian = $pdo->query(
    "SELECT t.*, p.nama_pelanggan
     FROM tb_transaksi_sewa t
     JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
     WHERE t.status = 'berjalan'
     ORDER BY t.tanggal_rencana_kembali ASC
     LIMIT 8"
)->fetchAll();

// --- Alat dengan stok menipis (nilai plus: insight tambahan) ---
$alat_menipis = $pdo->query(
    "SELECT * FROM tb_alat WHERE status='aktif' AND stok_tersedia <= 2 ORDER BY stok_tersedia ASC LIMIT 5"
)->fetchAll();

$judul_halaman = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h4>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-bg-primary h-100"><div class="card-body">
            <div class="fs-2 fw-bold"><?= (int) $total_alat ?></div>
            <div>Alat Aktif</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-success h-100"><div class="card-body">
            <div class="fs-2 fw-bold"><?= (int) $stok_tersedia ?></div>
            <div>Unit Stok Tersedia</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-warning h-100"><div class="card-body">
            <div class="fs-2 fw-bold"><?= (int) $transaksi_berjalan ?></div>
            <div>Transaksi Berjalan</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-danger h-100"><div class="card-body">
            <div class="fs-2 fw-bold"><?= (int) $transaksi_terlambat ?></div>
            <div>Transaksi Terlambat</div>
        </div></div>
    </div>
</div>

<div class="card card-body mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <span>Pendapatan Bulan Ini (dari pengembalian selesai)</span>
        <span class="fs-5 fw-bold text-success"><?= format_rupiah($pendapatan_bulan_ini) ?></span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Transaksi Perlu Perhatian (Berjalan / Jatuh Tempo)</div>
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Kode</th><th>Pelanggan</th><th>Rencana Kembali</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($transaksi_perhatian)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada transaksi berjalan.</td></tr>
                <?php endif; ?>
                <?php foreach ($transaksi_perhatian as $t): ?>
                    <?php $telat = strtotime($t['tanggal_rencana_kembali']) < strtotime(date('Y-m-d')); ?>
                    <tr>
                        <td><a href="../transaksi/detail.php?id=<?= (int) $t['id_transaksi'] ?>"><?= h($t['kode_transaksi']) ?></a></td>
                        <td><?= h($t['nama_pelanggan']) ?></td>
                        <td><?= format_tanggal($t['tanggal_rencana_kembali']) ?></td>
                        <td><span class="badge <?= $telat ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= $telat ? 'Terlambat' : 'Berjalan' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Stok Alat Menipis</div>
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Alat</th><th>Sisa Stok</th></tr></thead>
                <tbody>
                <?php if (empty($alat_menipis)): ?>
                    <tr><td colspan="2" class="text-center text-muted py-3">Semua stok aman.</td></tr>
                <?php endif; ?>
                <?php foreach ($alat_menipis as $a): ?>
                    <tr>
                        <td><?= h($a['nama_alat']) ?></td>
                        <td><span class="badge bg-danger"><?= (int) $a['stok_tersedia'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="cetak_pdf.php" class="btn btn-outline-dark btn-sm" target="_blank">
        <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan Transaksi (PDF)
    </a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
