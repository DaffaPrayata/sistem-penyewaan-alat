<?php
// modules/pengembalian/form_kembali.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$id_transaksi = (int) ($_GET['id_transaksi'] ?? 0);

if ($id_transaksi > 0) {
    // Mode langsung: sudah pilih transaksi dari halaman detail
    $stmt = $pdo->prepare(
        "SELECT t.*, p.nama_pelanggan
         FROM tb_transaksi_sewa t
         JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
         WHERE t.id_transaksi = :id AND t.status = 'berjalan'"
    );
    $stmt->execute([':id' => $id_transaksi]);
    $transaksi = $stmt->fetch();

    if (!$transaksi) {
        die('Transaksi tidak ditemukan atau sudah selesai.');
    }
} else {
    $transaksi = null;
}

// Daftar semua transaksi yang masih berjalan (untuk dipilih jika belum ada id_transaksi di URL)
$daftar_berjalan = $pdo->query(
    "SELECT t.*, p.nama_pelanggan
     FROM tb_transaksi_sewa t
     JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
     WHERE t.status = 'berjalan'
     ORDER BY t.tanggal_rencana_kembali ASC"
)->fetchAll();

$judul_halaman = 'Proses Pengembalian';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-box-arrow-in-left"></i> Proses Pengembalian</h4>

<?php if (!$transaksi): ?>
    <form method="get" action="" class="row g-2 mb-4">
        <div class="col-12 col-md-6">
            <select name="id_transaksi" class="form-select" required onchange="this.form.submit()">
                <option value="">-- Pilih Transaksi yang Masih Berjalan --</option>
                <?php foreach ($daftar_berjalan as $t): ?>
                    <option value="<?= (int) $t['id_transaksi'] ?>">
                        <?= h($t['kode_transaksi']) ?> — <?= h($t['nama_pelanggan']) ?>
                        (rencana kembali: <?= format_tanggal($t['tanggal_rencana_kembali']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
<?php else: ?>
    <div class="card card-body mb-3">
        <p class="mb-1">Kode Transaksi: <strong><?= h($transaksi['kode_transaksi']) ?></strong></p>
        <p class="mb-1">Pelanggan: <?= h($transaksi['nama_pelanggan']) ?></p>
        <p class="mb-1">Rencana Kembali: <?= format_tanggal($transaksi['tanggal_rencana_kembali']) ?></p>
        <p class="mb-0">Total Biaya Sewa: <?= format_rupiah($transaksi['total_biaya']) ?></p>
    </div>

    <form method="post" action="proses_kembali.php">
        <input type="hidden" name="id_transaksi" value="<?= (int) $transaksi['id_transaksi'] ?>">
        <div class="mb-3">
            <label class="form-label">Tanggal Kembali Aktual</label>
            <input type="date" name="tanggal_kembali_aktual" class="form-control" required
                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            <div class="form-text">
                Denda dihitung otomatis: <?= PERSEN_DENDA_PER_HARI * 100 ?>% dari total biaya sewa/hari, dikalikan jumlah hari terlambat.
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Kondisi alat saat kembali, dsb."></textarea>
        </div>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Selesaikan Transaksi</button>
        <a href="form_kembali.php" class="btn btn-outline-secondary">Batal</a>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
