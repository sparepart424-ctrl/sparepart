<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $phone, $hash);
            $stmt->execute();
            $success = 'Akun berhasil dibuat! Silakan masuk.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    body { background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; padding: 2rem 0; }
    .card { border: none; border-radius: 16px; }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="text-center mb-4">
        <a href="<?= BASE_URL ?>" class="text-decoration-none">
          <h3 class="text-warning fw-bold"><i class="bi bi-gear-fill me-2"></i>SparePart Store</h3>
        </a>
      </div>
      <div class="card shadow-lg p-4">
        <h5 class="fw-bold mb-4 text-center">Buat Akun Baru</h5>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success py-2 small">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
            <a href="<?= BASE_URL ?>/auth/login.php" class="alert-link">Masuk sekarang</a>
          </div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" name="name" class="form-control" placeholder="Nama lengkap"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="email@contoh.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Nomor HP</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-telephone"></i></span>
              <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Konfirmasi Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-warning w-100 fw-bold">
            <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
          </button>
        </form>
        <hr>
        <p class="text-center small mb-0">
          Sudah punya akun? <a href="<?= BASE_URL ?>/auth/login.php" class="text-warning fw-semibold">Masuk</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
