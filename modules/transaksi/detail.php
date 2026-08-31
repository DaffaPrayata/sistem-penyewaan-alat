<?php
// modules/transaksi/detail.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT t.*, p.nama_pelanggan, p.no_hp, p.no_identitas, u.nama_lengkap AS nama_petugas
     FROM tb_transaksi_sewa t
     JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
     JOIN tb_users u ON u.id_user = t.id_user
     WHERE t.id_transaksi = :id"
);
$stmt->execute([':id' => $id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    die('Transaksi tidak ditemukan.');
}

$stmt = $pdo->prepare(
    "SELECT d.*, a.nama_alat, a.kode_alat
     FROM tb_detail_sewa d
     JOIN tb_alat a ON a.id_alat = d.id_alat
     WHERE d.id_transaksi = :id"
);
$stmt->execute([':id' => $id]);
$detail = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM tb_pengembalian WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$pengembalian = $stmt->fetch();

$judul_halaman = 'Detail Transaksi';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success">Transaksi berhasil dibuat.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt"></i> Detail Transaksi — <?= h($transaksi['kode_transaksi']) ?></h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h6>Informasi Pelanggan</h6>
            <p class="mb-1"><strong><?= h($transaksi['nama_pelanggan']) ?></strong></p>
            <p class="mb-1 text-muted"><?= h($transaksi['no_identitas']) ?> — <?= h($transaksi['no_hp']) ?></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h6>Informasi Transaksi</h6>
            <p class="mb-1">Petugas: <?= h($transaksi['nama_petugas']) ?></p>
            <p class="mb-1">Tanggal Sewa: <?= format_tanggal($transaksi['tanggal_sewa']) ?></p>
            <p class="mb-1">Rencana Kembali: <?= format_tanggal($transaksi['tanggal_rencana_kembali']) ?></p>
            <p class="mb-0">Status:
                <span class="badge <?= $transaksi['status'] === 'selesai' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= h(ucfirst($transaksi['status'])) ?>
                </span>
            </p>
        </div>
    </div>
</div>

<div class="table-responsive mb-3">
<table class="table table-bordered align-middle">
    <thead class="table-light">
        <tr><th>Alat</th><th>Jumlah</th><th>Harga (total durasi)</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
        <?php foreach ($detail as $d): ?>
        <tr>
            <td><?= h($d['nama_alat']) ?> <small class="text-muted">(<?= h($d['kode_alat']) ?>)</small></td>
            <td><?= (int) $d['jumlah'] ?></td>
            <td><?= format_rupiah($d['harga_satuan']) ?></td>
            <td><?= format_rupiah($d['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="table-light">
            <th colspan="3" class="text-end">Total Biaya Sewa</th>
            <th><?= format_rupiah($transaksi['total_biaya']) ?></th>
        </tr>
    </tfoot>
</table>
</div>

<?php if ($pengembalian): ?>
    <div class="card card-body border-success mb-3">
        <h6 class="text-success"><i class="bi bi-check-circle"></i> Pengembalian Selesai</h6>
        <p class="mb-1">Tanggal Kembali: <?= format_tanggal($pengembalian['tanggal_kembali_aktual']) ?></p>
        <p class="mb-1">Hari Terlambat: <?= (int) $pengembalian['jumlah_hari_terlambat'] ?> hari</p>
        <p class="mb-1">Denda: <?= format_rupiah($pengembalian['denda']) ?></p>
        <p class="mb-0">Total Pembayaran: <strong><?= format_rupiah($pengembalian['total_pembayaran']) ?></strong></p>
    </div>
<?php elseif ($transaksi['status'] === 'berjalan'): ?>
    <a href="../pengembalian/form_kembali.php?id_transaksi=<?= (int) $transaksi['id_transaksi'] ?>" class="btn btn-success">
        <i class="bi bi-box-arrow-in-left"></i> Proses Pengembalian
    </a>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
