<?php
// modules/pelanggan/tambah.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login(); // admin & petugas boleh menambah pelanggan baru saat transaksi

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama_pelanggan'] ?? '');
    $noid    = trim($_POST['no_identitas'] ?? '');
    $nohp    = trim($_POST['no_hp'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $noid === '' || $nohp === '') {
        $error = 'Nama, No. Identitas, dan No. HP wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO tb_pelanggan (nama_pelanggan, no_identitas, no_hp, alamat)
                 VALUES (:nama, :noid, :nohp, :alamat)"
            );
            $stmt->execute([
                ':nama'   => $nama,
                ':noid'   => $noid,
                ':nohp'   => $nohp,
                ':alamat' => $alamat,
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'No. Identitas sudah terdaftar.';
        }
    }
}

$judul_halaman = 'Tambah Pelanggan';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-person-plus"></i> Tambah Pelanggan</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<form method="post" action="" class="card card-body" style="max-width:600px;">
    <div class="mb-3">
        <label class="form-label">Nama Pelanggan</label>
        <input type="text" name="nama_pelanggan" class="form-control" required maxlength="100">
    </div>
    <div class="mb-3">
        <label class="form-label">No. Identitas (KTP)</label>
        <input type="text" name="no_identitas" class="form-control" required maxlength="30">
    </div>
    <div class="mb-3">
        <label class="form-label">No. HP</label>
        <input type="text" name="no_hp" class="form-control" required maxlength="20">
    </div>
    <div class="mb-3">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="3"></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
