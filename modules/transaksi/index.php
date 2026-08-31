<?php
// modules/transaksi/index.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$status_filter = $_GET['status'] ?? '';

$sql = "SELECT t.*, p.nama_pelanggan, u.nama_lengkap AS nama_petugas
        FROM tb_transaksi_sewa t
        JOIN tb_pelanggan p ON p.id_pelanggan = t.id_pelanggan
        JOIN tb_users u ON u.id_user = t.id_user
        WHERE 1=1";
$params = [];
if ($status_filter !== '') {
    $sql .= " AND t.status = :status";
    $params[':status'] = $status_filter;
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_transaksi = $stmt->fetchAll();

// Tandai transaksi 'berjalan' yang sebenarnya sudah lewat tanggal rencana kembali sebagai 'terlambat' (tampilan saja)
foreach ($daftar_transaksi as &$t) {
    if ($t['status'] === 'berjalan' && strtotime($t['tanggal_rencana_kembali']) < strtotime(date('Y-m-d'))) {
        $t['status_tampil'] = 'terlambat';
    } else {
        $t['status_tampil'] = $t['status'];
    }
}
unset($t);

$judul_halaman = 'Transaksi Sewa';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt"></i> Transaksi Sewa</h4>
    <a href="sewa_baru.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Transaksi Baru</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-8 col-md-3">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="berjalan" <?= $status_filter === 'berjalan' ? 'selected' : '' ?>>Berjalan</option>
            <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select>
    </div>
    <div class="col-4 col-md-2">
        <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
        <tr>
            <th>Kode</th><th>Pelanggan</th><th>Petugas</th><th>Tgl Sewa</th>
            <th>Rencana Kembali</th><th>Total Biaya</th><th>Status</th><th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($daftar_transaksi)): ?>
        <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
    <?php endif; ?>
    <?php foreach ($daftar_transaksi as $t): ?>
        <?php
            $badge = [
                'berjalan'  => 'bg-warning text-dark',
                'selesai'   => 'bg-success',
                'terlambat' => 'bg-danger',
            ][$t['status_tampil']];
        ?>
        <tr>
            <td><?= h($t['kode_transaksi']) ?></td>
            <td><?= h($t['nama_pelanggan']) ?></td>
            <td><?= h($t['nama_petugas']) ?></td>
            <td><?= format_tanggal($t['tanggal_sewa']) ?></td>
            <td><?= format_tanggal($t['tanggal_rencana_kembali']) ?></td>
            <td><?= format_rupiah($t['total_biaya']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= h(ucfirst($t['status_tampil'])) ?></span></td>
            <td><a href="detail.php?id=<?= (int) $t['id_transaksi'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> Detail</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
