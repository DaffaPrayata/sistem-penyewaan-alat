<?php
// modules/transaksi/sewa_baru.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login(); // admin & petugas boleh transaksi

$daftar_pelanggan = $pdo->query("SELECT * FROM tb_pelanggan ORDER BY nama_pelanggan")->fetchAll();

// Hanya tampilkan alat aktif yang stoknya masih tersedia
$daftar_alat = $pdo->query(
    "SELECT * FROM tb_alat WHERE status = 'aktif' AND stok_tersedia > 0 ORDER BY nama_alat"
)->fetchAll();

$judul_halaman = 'Transaksi Sewa Baru';
require_once __DIR__ . '/../../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-cart-plus"></i> Transaksi Sewa Baru</h4>

<form method="post" action="proses_sewa.php" id="formSewa">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Pelanggan</label>
            <select name="id_pelanggan" class="form-select" required>
                <option value="">-- Pilih Pelanggan --</option>
                <?php foreach ($daftar_pelanggan as $p): ?>
                    <option value="<?= (int) $p['id_pelanggan'] ?>">
                        <?= h($p['nama_pelanggan']) ?> — <?= h($p['no_hp']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Pelanggan baru? <a href="../pelanggan/tambah.php" target="_blank">Tambah di sini</a>, lalu muat ulang halaman ini.</div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Tanggal Sewa</label>
            <input type="date" name="tanggal_sewa" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Rencana Kembali</label>
            <input type="date" name="tanggal_rencana_kembali" class="form-control" required
                   value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
    </div>

    <hr class="my-4">
    <h6>Pilih Alat yang Disewa</h6>
    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr><th>Sewa</th><th>Nama Alat</th><th>Stok Tersedia</th><th>Harga/Hari</th><th style="width:120px;">Jumlah</th></tr>
        </thead>
        <tbody>
        <?php if (empty($daftar_alat)): ?>
            <tr><td colspan="5" class="text-center text-muted">Tidak ada alat yang tersedia saat ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($daftar_alat as $a): ?>
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input cek-alat" name="id_alat[]" value="<?= (int) $a['id_alat'] ?>">
                </td>
                <td><?= h($a['nama_alat']) ?> <small class="text-muted">(<?= h($a['kode_alat']) ?>)</small></td>
                <td><?= (int) $a['stok_tersedia'] ?></td>
                <td><?= format_rupiah($a['harga_sewa_per_hari']) ?></td>
                <td>
                    <input type="number" name="jumlah[<?= (int) $a['id_alat'] ?>]" class="form-control form-control-sm"
                           min="1" max="<?= (int) $a['stok_tersedia'] ?>" value="1">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Proses Transaksi</button>
    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
