<?php
// includes/navbar.php
$role_login = $_SESSION['nama_role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/modules/laporan/dashboard.php">
      <i class="bi bi-box-seam"></i> <?= h(APP_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <?php if ($role_login): ?>
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/laporan/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/alat/index.php">Data Alat</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/pelanggan/index.php">Pelanggan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/transaksi/index.php">Transaksi Sewa</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/pengembalian/form_kembali.php">Pengembalian</a></li>
      </ul>
      <span class="navbar-text text-light me-3">
        <i class="bi bi-person-circle"></i> <?= h($_SESSION['nama_lengkap'] ?? '') ?>
        <span class="badge bg-secondary ms-1"><?= h($role_login) ?></span>
      </span>
      <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
