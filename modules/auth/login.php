<?php
// modules/auth/login.php
session_start();
require_once __DIR__ . '/../../config/konstanta.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user'])) {
    header('Location: ' . BASE_URL . '/modules/laporan/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Prepared statement: input user tidak pernah digabung langsung ke query
        $stmt = $pdo->prepare(
            "SELECT u.id_user, u.username, u.password, u.nama_lengkap, u.status_aktif, r.nama_role
             FROM tb_users u
             JOIN tb_role r ON r.id_role = u.id_role
             WHERE u.username = :username
             LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $akun = $stmt->fetch();

        if ($akun && (int) $akun['status_aktif'] === 1 && password_verify($password, $akun['password'])) {
            $_SESSION['id_user']      = $akun['id_user'];
            $_SESSION['username']     = $akun['username'];
            $_SESSION['nama_lengkap'] = $akun['nama_lengkap'];
            $_SESSION['nama_role']    = $akun['nama_role'];

            header('Location: ' . BASE_URL . '/modules/laporan/dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow-sm" style="width:100%; max-width:380px;">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-3"><?= h(APP_NAME) ?></h4>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Masuk</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
