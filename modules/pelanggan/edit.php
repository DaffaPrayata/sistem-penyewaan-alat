<?php
// modules/pelanggan/edit.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_pelanggan WHERE id_pelanggan = :id");
$stmt->execute([':id' => $id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    die('Data pelanggan tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama_pelanggan'] ?? '');
    $noid   = trim($_POST['no_identitas'] ?? '');
    $nohp   = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $noid === '' || $nohp === '') {
        $error = 'Nama, No. Identitas, dan No. HP wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE tb_pelanggan
                 SET nama_pelanggan = :nama, no_identitas = :noid, no_hp = :nohp, alamat = :alamat
                 WHERE id_pelanggan = :id"
            );
            $stmt->execute([
                ':nama' => $nama, ':noid' => $noid, ':nohp' => $nohp, ':alamat' => $alamat, ':id' => $id,
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'No. Identitas sudah dipakai pelanggan lain.';
        }
    }
}

$judul_halaman = 'Edit Pelanggan';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-pencil"></i> Edit Pelanggan</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<form method="post" action="" class="card card-body" style="max-width:600px;">
    <div class="mb-3">
        <label class="form-label">Nama Pelanggan</label>
        <input type="text" name="nama_pelanggan" class="form-control" required value="<?= h($pelanggan['nama_pelanggan']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">No. Identitas (KTP)</label>
        <input type="text" name="no_identitas" class="form-control" required value="<?= h($pelanggan['no_identitas']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">No. HP</label>
        <input type="text" name="no_hp" class="form-control" required value="<?= h($pelanggan['no_hp']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="3"><?= h($pelanggan['alamat']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
