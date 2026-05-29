<div class="sidebar p-3 d-flex flex-column" style="width:220px;min-width:220px">
  <a href="<?= BASE_URL ?>" class="text-decoration-none mb-4">
    <h6 class="text-warning fw-bold mb-0"><i class="bi bi-gear-fill me-2"></i>SparePart</h6>
    <small class="text-secondary">Admin Panel</small>
  </a>
  <nav class="nav flex-column flex-grow-1">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/index.php">
      <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/products.php">
      <i class="bi bi-box-seam me-2"></i>Produk
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/categories.php">
      <i class="bi bi-tags me-2"></i>Kategori
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/orders.php">
      <i class="bi bi-bag-check me-2"></i>Pesanan
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/users.php">
      <i class="bi bi-people me-2"></i>Users
    </a>
  </nav>
  <div class="mt-auto">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger">
      <i class="bi bi-box-arrow-right me-2"></i>Keluar
    </a>
  </div>
</div>
