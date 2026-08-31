<?php
// modules/pelanggan/hapus.php
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
cek_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

$cek = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_transaksi_sewa WHERE id_pelanggan = :id");
$cek->execute([':id' => $id]);

if ((int) $cek->fetch()['total'] > 0) {
    header('Location: index.php?error=masih_ada_transaksi');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM tb_pelanggan WHERE id_pelanggan = :id");
$stmt->execute([':id' => $id]);

header('Location: index.php');
exit;
