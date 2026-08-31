<?php
// modules/transaksi/proses_sewa.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sewa_baru.php');
    exit;
}

$id_pelanggan   = $_POST['id_pelanggan'] ?? '';
$tanggal_sewa   = $_POST['tanggal_sewa'] ?? '';
$tanggal_kembali = $_POST['tanggal_rencana_kembali'] ?? '';
$id_alat_list   = $_POST['id_alat'] ?? [];
$jumlah_list    = $_POST['jumlah'] ?? [];

// --- Validasi dasar ---
if ($id_pelanggan === '' || $tanggal_sewa === '' || $tanggal_kembali === '' || empty($id_alat_list)) {
    die('Data tidak lengkap. Pastikan pelanggan, tanggal, dan minimal satu alat dipilih. <a href="sewa_baru.php">Kembali</a>');
}
if (strtotime($tanggal_kembali) < strtotime($tanggal_sewa)) {
    die('Tanggal rencana kembali tidak boleh sebelum tanggal sewa. <a href="sewa_baru.php">Kembali</a>');
}

// id_alat[] hanya berisi checkbox yang DICENTANG. jumlah[] adalah array asosiatif
// yang di-key langsung dengan id_alat (lihat name="jumlah[<id_alat>]" di sewa_baru.php),
// sehingga tidak bergantung pada urutan/index array dan tidak rawan salah pasangan.
$item_sewa = [];
foreach ($id_alat_list as $id_alat) {
    $id_alat = (int) $id_alat;
    $jml = isset($jumlah_list[$id_alat]) ? (int) $jumlah_list[$id_alat] : 0;
    if ($jml > 0) {
        $item_sewa[$id_alat] = ($item_sewa[$id_alat] ?? 0) + $jml;
    }
}

if (empty($item_sewa)) {
    die('Tidak ada alat valid yang dipilih. <a href="sewa_baru.php">Kembali</a>');
}

try {
    $pdo->beginTransaction();

    // Lock baris alat yang akan disewa agar tidak terjadi race condition stok (FOR UPDATE)
    $total_biaya = 0;
    $durasi_hari = max(1, (strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400);
    $detail_final = [];

    foreach ($item_sewa as $id_alat => $jumlah) {
        $stmt = $pdo->prepare("SELECT * FROM tb_alat WHERE id_alat = :id FOR UPDATE");
        $stmt->execute([':id' => $id_alat]);
        $alat = $stmt->fetch();

        if (!$alat || $alat['status'] !== 'aktif') {
            throw new Exception('Alat tidak ditemukan atau nonaktif.');
        }
        if ($alat['stok_tersedia'] < $jumlah) {
            throw new Exception('Stok "' . $alat['nama_alat'] . '" tidak mencukupi (tersedia: ' . $alat['stok_tersedia'] . ').');
        }

        $subtotal = $jumlah * $alat['harga_sewa_per_hari'] * $durasi_hari;
        $total_biaya += $subtotal;

        $detail_final[] = [
            'id_alat'      => $id_alat,
            'jumlah'       => $jumlah,
            'harga_satuan' => $alat['harga_sewa_per_hari'] * $durasi_hari, // harga per unit untuk durasi sewa ini
        ];
    }

    $kode_transaksi = generate_kode('TRX');

    $stmt = $pdo->prepare(
        "INSERT INTO tb_transaksi_sewa (kode_transaksi, id_pelanggan, id_user, tanggal_sewa, tanggal_rencana_kembali, total_biaya)
         VALUES (:kode, :id_pelanggan, :id_user, :tgl_sewa, :tgl_kembali, :total)"
    );
    $stmt->execute([
        ':kode'         => $kode_transaksi,
        ':id_pelanggan' => $id_pelanggan,
        ':id_user'      => $_SESSION['id_user'],
        ':tgl_sewa'     => $tanggal_sewa,
        ':tgl_kembali'  => $tanggal_kembali,
        ':total'        => $total_biaya,
    ]);
    $id_transaksi = $pdo->lastInsertId();

    // Insert detail -> trigger trg_kurangi_stok otomatis mengurangi stok_tersedia
    $stmtDetail = $pdo->prepare(
        "INSERT INTO tb_detail_sewa (id_transaksi, id_alat, jumlah, harga_satuan)
         VALUES (:id_transaksi, :id_alat, :jumlah, :harga_satuan)"
    );
    foreach ($detail_final as $d) {
        $stmtDetail->execute([
            ':id_transaksi' => $id_transaksi,
            ':id_alat'      => $d['id_alat'],
            ':jumlah'       => $d['jumlah'],
            ':harga_satuan' => $d['harga_satuan'],
        ]);
    }

    $pdo->commit();
    header('Location: detail.php?id=' . $id_transaksi . '&sukses=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die('Transaksi gagal: ' . h($e->getMessage()) . ' <a href="sewa_baru.php">Kembali</a>');
}
