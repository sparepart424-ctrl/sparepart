<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

$cartCount = 0;
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $r   = db()->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    $cartCount = (int)($r->fetch_assoc()['total'] ?? 0);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">
      <i class="bi bi-gear-fill text-warning me-2"></i>SparePart Store
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pages/home.php">Produk</a></li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>/pages/cart.php">
              <i class="bi bi-cart3 fs-5"></i>
              <?php if ($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                  <?= $cartCount ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
          <?php if (isAdmin()): ?>
            <li class="nav-item">
              <a class="btn btn-sm btn-outline-warning" href="<?= BASE_URL ?>/admin/index.php">
                <i class="bi bi-speedometer2 me-1"></i>Admin
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/pages/orders.php">Pesanan Saya</a>
            </li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['name']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Keluar
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light me-1" href="<?= BASE_URL ?>/auth/login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-warning text-dark" href="<?= BASE_URL ?>/auth/register.php">
              <i class="bi bi-person-plus me-1"></i>Daftar
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
