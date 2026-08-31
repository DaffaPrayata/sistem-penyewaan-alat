<?php
// modules/alat/index.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login(); // admin & petugas boleh melihat

$keyword    = trim($_GET['q'] ?? '');
$id_kategori = $_GET['kategori'] ?? '';

// Bangun query secara dinamis TAPI tetap pakai parameter binding (bukan concat langsung)
$sql = "SELECT a.*, k.nama_kategori
        FROM tb_alat a
        JOIN tb_kategori_alat k ON k.id_kategori = a.id_kategori
        WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (a.nama_alat LIKE :keyword OR a.kode_alat LIKE :keyword)";
    $params[':keyword'] = '%' . $keyword . '%';
}
if ($id_kategori !== '') {
    $sql .= " AND a.id_kategori = :id_kategori";
    $params[':id_kategori'] = $id_kategori;
}
$sql .= " ORDER BY a.nama_alat ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_alat = $stmt->fetchAll();

$daftar_kategori = $pdo->query("SELECT * FROM tb_kategori_alat ORDER BY nama_kategori")->fetchAll();

$judul_halaman = 'Data Alat';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-tools"></i> Data Alat</h4>
    <?php if ($_SESSION['nama_role'] === 'admin'): ?>
    <a href="tambah.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Alat</a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Cari nama / kode alat..." value="<?= h($keyword) ?>">
    </div>
    <div class="col-8 col-md-4">
        <select name="kategori" class="form-select">
            <option value="">Semua Kategori</option>
            <?php foreach ($daftar_kategori as $k): ?>
                <option value="<?= (int) $k['id_kategori'] ?>" <?= ($id_kategori == $k['id_kategori']) ? 'selected' : '' ?>>
                    <?= h($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-3">
        <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Cari</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
        <tr>
            <th>Kode</th><th>Nama Alat</th><th>Kategori</th>
            <th>Stok Tersedia</th><th>Stok Total</th><th>Harga/Hari</th><th>Status</th>
            <?php if ($_SESSION['nama_role'] === 'admin'): ?><th>Aksi</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($daftar_alat)): ?>
        <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
    <?php endif; ?>
    <?php foreach ($daftar_alat as $alat): ?>
        <tr>
            <td><?= h($alat['kode_alat']) ?></td>
            <td><?= h($alat['nama_alat']) ?></td>
            <td><?= h($alat['nama_kategori']) ?></td>
            <td>
                <span class="badge <?= $alat['stok_tersedia'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                    <?= (int) $alat['stok_tersedia'] ?>
                </span>
            </td>
            <td><?= (int) $alat['stok_total'] ?></td>
            <td><?= format_rupiah($alat['harga_sewa_per_hari']) ?></td>
            <td><?= $alat['status'] === 'aktif' ? '<span class="badge bg-primary">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
            <?php if ($_SESSION['nama_role'] === 'admin'): ?>
            <td>
                <a href="edit.php?id=<?= (int) $alat['id_alat'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                <a href="hapus.php?id=<?= (int) $alat['id_alat'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin hapus alat ini?');"><i class="bi bi-trash"></i></a>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
