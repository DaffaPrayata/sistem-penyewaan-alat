<?php
// modules/pengembalian/proses_kembali.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_kembali.php');
    exit;
}

$id_transaksi   = (int) ($_POST['id_transaksi'] ?? 0);
$tanggal_aktual = $_POST['tanggal_kembali_aktual'] ?? '';
$catatan        = trim($_POST['catatan'] ?? '');

if ($id_transaksi <= 0 || $tanggal_aktual === '') {
    die('Data tidak lengkap. <a href="form_kembali.php">Kembali</a>');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM tb_transaksi_sewa WHERE id_transaksi = :id AND status = 'berjalan' FOR UPDATE");
    $stmt->execute([':id' => $id_transaksi]);
    $transaksi = $stmt->fetch();

    if (!$transaksi) {
        throw new Exception('Transaksi tidak ditemukan atau sudah diselesaikan sebelumnya.');
    }

    // --- Perhitungan otomatis: hari terlambat & denda ---
    $hari_terlambat = hitung_hari_terlambat($transaksi['tanggal_rencana_kembali'], $tanggal_aktual);

    $denda = 0;
    if ($hari_terlambat > 0) {
        // Denda = persentase x total biaya sewa/hari x jumlah hari terlambat
        // (total_biaya sudah mencakup seluruh durasi awal, jadi kita hitung biaya/hari dari situ)
        $stmtDurasi = $pdo->prepare(
            "SELECT DATEDIFF(tanggal_rencana_kembali, tanggal_sewa) AS durasi FROM tb_transaksi_sewa WHERE id_transaksi = :id"
        );
        $stmtDurasi->execute([':id' => $id_transaksi]);
        $durasi = max(1, (int) $stmtDurasi->fetch()['durasi']);

        $biaya_per_hari = $transaksi['total_biaya'] / $durasi;
        $denda = round(PERSEN_DENDA_PER_HARI * $biaya_per_hari * $hari_terlambat, 2);
    }

    $total_pembayaran = $transaksi['total_biaya'] + $denda;

    // Insert pengembalian -> trigger trg_kembalikan_stok otomatis:
    //   1) mengembalikan stok_tersedia alat terkait
    //   2) mengubah status transaksi menjadi 'selesai'
    $stmt = $pdo->prepare(
        "INSERT INTO tb_pengembalian
            (id_transaksi, id_user, tanggal_kembali_aktual, jumlah_hari_terlambat, denda, total_pembayaran, catatan)
         VALUES (:id_transaksi, :id_user, :tgl_aktual, :hari_terlambat, :denda, :total, :catatan)"
    );
    $stmt->execute([
        ':id_transaksi'    => $id_transaksi,
        ':id_user'         => $_SESSION['id_user'],
        ':tgl_aktual'      => $tanggal_aktual,
        ':hari_terlambat'  => $hari_terlambat,
        ':denda'           => $denda,
        ':total'           => $total_pembayaran,
        ':catatan'         => $catatan,
    ]);

    $pdo->commit();
    header('Location: ../transaksi/detail.php?id=' . $id_transaksi . '&pengembalian_sukses=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die('Gagal memproses pengembalian: ' . h($e->getMessage()) . ' <a href="form_kembali.php">Kembali</a>');
}
