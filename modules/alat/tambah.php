<?php
// modules/alat/tambah.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_role(['admin']); // hanya admin boleh menambah alat

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = $_POST['id_kategori'] ?? '';
    $kode_alat   = trim($_POST['kode_alat'] ?? '');
    $nama_alat   = trim($_POST['nama_alat'] ?? '');
    $stok_total  = (int) ($_POST['stok_total'] ?? 0);
    $harga       = (float) ($_POST['harga_sewa_per_hari'] ?? 0);

    if ($id_kategori === '' || $kode_alat === '' || $nama_alat === '' || $stok_total < 0 || $harga <= 0) {
        $error = 'Semua field wajib diisi dengan benar.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_alat (id_kategori, kode_alat, nama_alat, stok_total, stok_tersedia, harga_sewa_per_hari)
             VALUES (:id_kategori, :kode_alat, :nama_alat, :stok_total, :stok_tersedia, :harga)"
        );
        try {
            $stmt->execute([
                ':id_kategori'   => $id_kategori,
                ':kode_alat'     => $kode_alat,
                ':nama_alat'     => $nama_alat,
                ':stok_total'    => $stok_total,
                ':stok_tersedia' => $stok_total, // saat baru dibuat, semua stok tersedia
                ':harga'         => $harga,
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Kode alat sudah digunakan atau data tidak valid.';
        }
    }
}

$daftar_kategori = $pdo->query("SELECT * FROM tb_kategori_alat ORDER BY nama_kategori")->fetchAll();

$judul_halaman = 'Tambah Alat';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-plus-lg"></i> Tambah Alat</h4>

<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<form method="post" action="" class="card card-body" style="max-width:600px;">
    <div class="mb-3">
        <label class="form-label">Kategori</label>
        <select name="id_kategori" class="form-select" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($daftar_kategori as $k): ?>
                <option value="<?= (int) $k['id_kategori'] ?>"><?= h($k['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Kode Alat</label>
        <input type="text" name="kode_alat" class="form-control" required maxlength="20" placeholder="Contoh: TND-002">
    </div>
    <div class="mb-3">
        <label class="form-label">Nama Alat</label>
        <input type="text" name="nama_alat" class="form-control" required maxlength="100">
    </div>
    <div class="mb-3">
        <label class="form-label">Stok Total</label>
        <input type="number" name="stok_total" class="form-control" required min="0">
    </div>
    <div class="mb-3">
        <label class="form-label">Harga Sewa / Hari (Rp)</label>
        <input type="number" name="harga_sewa_per_hari" class="form-control" required min="0" step="0.01">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
