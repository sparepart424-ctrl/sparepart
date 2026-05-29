<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL); exit; }

$error    = '';
$redirect = $_GET['redirect'] ?? BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = db()->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? BASE_URL . '/admin/index.php' : $redirect));
            exit;
        }
        $error = 'Email atau password salah.';
    } else {
        $error = 'Semua field wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    body { background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; }
    .card { border: none; border-radius: 16px; }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="text-center mb-4">
        <a href="<?= BASE_URL ?>" class="text-decoration-none">
          <h3 class="text-warning fw-bold"><i class="bi bi-gear-fill me-2"></i>SparePart Store</h3>
        </a>
      </div>
      <div class="card shadow-lg p-4">
        <h5 class="fw-bold mb-4 text-center">Masuk ke Akun</h5>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="email@contoh.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-warning w-100 fw-bold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
          </button>
        </form>
        <hr>
        <p class="text-center small mb-0">
          Belum punya akun? <a href="<?= BASE_URL ?>/auth/register.php" class="text-warning fw-semibold">Daftar sekarang</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
