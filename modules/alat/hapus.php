<?php
// modules/alat/hapus.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

// Cegah hapus alat yang masih punya riwayat transaksi (jaga integritas data laporan).
$cek = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_detail_sewa WHERE id_alat = :id");
$cek->execute([':id' => $id]);
$masihDipakai = (int) $cek->fetch()['total'] > 0;

if ($masihDipakai) {
    // Nonaktifkan saja, jangan dihapus, supaya riwayat transaksi lama tetap valid.
    $stmt = $pdo->prepare("UPDATE tb_alat SET status = 'nonaktif' WHERE id_alat = :id");
    $stmt->execute([':id' => $id]);
} else {
    $stmt = $pdo->prepare("DELETE FROM tb_alat WHERE id_alat = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: index.php');
exit;
