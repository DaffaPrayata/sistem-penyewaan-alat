<?php
// modules/alat/edit.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tb_alat WHERE id_alat = :id");
$stmt->execute([':id' => $id]);
$alat = $stmt->fetch();

if (!$alat) {
    die('Data alat tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = $_POST['id_kategori'] ?? '';
    $nama_alat   = trim($_POST['nama_alat'] ?? '');
    $stok_total  = (int) ($_POST['stok_total'] ?? 0);
    $harga       = (float) ($_POST['harga_sewa_per_hari'] ?? 0);
    $status      = $_POST['status'] ?? 'aktif';

    // Selisih stok total lama vs baru diterapkan juga ke stok_tersedia,
    // supaya stok yang sedang disewa (selisih total-tersedia) tidak berubah.
    $selisih = $stok_total - (int) $alat['stok_total'];
    $stok_tersedia_baru = max(0, (int) $alat['stok_tersedia'] + $selisih);

    if ($id_kategori === '' || $nama_alat === '' || $stok_total < 0 || $harga <= 0) {
        $error = 'Semua field wajib diisi dengan benar.';
    } else {
        $stmt = $pdo->prepare(
            "UPDATE tb_alat
             SET id_kategori = :id_kategori, nama_alat = :nama_alat,
                 stok_total = :stok_total, stok_tersedia = :stok_tersedia,
                 harga_sewa_per_hari = :harga, status = :status
             WHERE id_alat = :id"
        );
        $stmt->execute([
            ':id_kategori'   => $id_kategori,
            ':nama_alat'     => $nama_alat,
            ':stok_total'    => $stok_total,
            ':stok_tersedia' => $stok_tersedia_baru,
            ':harga'         => $harga,
            ':status'        => $status,
            ':id'            => $id,
        ]);
        header('Location: index.php');
        exit;
    }
}

$daftar_kategori = $pdo->query("SELECT * FROM tb_kategori_alat ORDER BY nama_kategori")->fetchAll();

$judul_halaman = 'Edit Alat';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-pencil"></i> Edit Alat</h4>

<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<form method="post" action="" class="card card-body" style="max-width:600px;">
    <div class="mb-3">
        <label class="form-label">Kategori</label>
        <select name="id_kategori" class="form-select" required>
            <?php foreach ($daftar_kategori as $k): ?>
                <option value="<?= (int) $k['id_kategori'] ?>" <?= $k['id_kategori'] == $alat['id_kategori'] ? 'selected' : '' ?>>
                    <?= h($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Kode Alat</label>
        <input type="text" class="form-control" value="<?= h($alat['kode_alat']) ?>" disabled>
        <div class="form-text">Kode alat tidak dapat diubah.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Nama Alat</label>
        <input type="text" name="nama_alat" class="form-control" required value="<?= h($alat['nama_alat']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Stok Total</label>
        <input type="number" name="stok_total" class="form-control" required min="0" value="<?= (int) $alat['stok_total'] ?>">
        <div class="form-text">Stok tersedia saat ini: <?= (int) $alat['stok_tersedia'] ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Harga Sewa / Hari (Rp)</label>
        <input type="number" name="harga_sewa_per_hari" class="form-control" required min="0" step="0.01" value="<?= (float) $alat['harga_sewa_per_hari'] ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="aktif" <?= $alat['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $alat['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
