<?php
// modules/pelanggan/index.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$keyword = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM tb_pelanggan WHERE 1=1";
$params = [];
if ($keyword !== '') {
    $sql .= " AND (nama_pelanggan LIKE :kw OR no_identitas LIKE :kw OR no_hp LIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}
$sql .= " ORDER BY nama_pelanggan ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_pelanggan = $stmt->fetchAll();

$judul_halaman = 'Data Pelanggan';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people"></i> Data Pelanggan</h4>
    <a href="tambah.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Pelanggan</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-9 col-md-6">
        <input type="text" name="q" class="form-control" placeholder="Cari nama / no. identitas / no. HP..." value="<?= h($keyword) ?>">
    </div>
    <div class="col-3 col-md-2">
        <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Cari</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
        <tr><th>Nama</th><th>No. Identitas</th><th>No. HP</th><th>Alamat</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if (empty($daftar_pelanggan)): ?>
        <tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>
    <?php endif; ?>
    <?php foreach ($daftar_pelanggan as $p): ?>
        <tr>
            <td><?= h($p['nama_pelanggan']) ?></td>
            <td><?= h($p['no_identitas']) ?></td>
            <td><?= h($p['no_hp']) ?></td>
            <td><?= h($p['alamat']) ?></td>
            <td>
                <a href="edit.php?id=<?= (int) $p['id_pelanggan'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                <?php if ($_SESSION['nama_role'] === 'admin'): ?>
                <a href="hapus.php?id=<?= (int) $p['id_pelanggan'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin hapus pelanggan ini?');"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
