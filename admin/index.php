<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireAdmin();

$totalProducts = db()->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalOrders   = db()->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalUsers    = db()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalRevenue  = db()->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status IN ('paid','processing','shipped','completed')")->fetch_row()[0];

$recentOrders = db()->query("
    SELECT o.*, u.name as user_name FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$statusBadge = ['pending'=>'warning','paid'=>'success','processing'=>'info','shipped'=>'primary','completed'=>'success','cancelled'=>'danger'];
$statusLabel = ['pending'=>'Menunggu','paid'=>'Dibayar','processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Batal'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    .sidebar { min-height: 100vh; background: #1a1a2e; }
    .sidebar .nav-link { color: rgba(255,255,255,.7); border-radius: 8px; margin-bottom: 4px; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,193,7,.15); color: #ffc107; }
    .sidebar .nav-link i { width: 20px; }
    .stat-card { border-radius: 12px; border: none; }
  </style>
</head>
<body class="bg-light">
<div class="d-flex">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <!-- Main -->
  <div class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0">Dashboard</h4>
      <span class="text-muted small">Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>!</span>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm bg-warning bg-gradient text-dark p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div><p class="mb-1 small fw-semibold">Total Produk</p><h3 class="fw-bold mb-0"><?= $totalProducts ?></h3></div>
            <i class="bi bi-box-seam fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm bg-success bg-gradient text-white p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div><p class="mb-1 small fw-semibold">Total Pesanan</p><h3 class="fw-bold mb-0"><?= $totalOrders ?></h3></div>
            <i class="bi bi-bag-check fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm bg-info bg-gradient text-white p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div><p class="mb-1 small fw-semibold">Total Users</p><h3 class="fw-bold mb-0"><?= $totalUsers ?></h3></div>
            <i class="bi bi-people fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm bg-dark text-white p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div><p class="mb-1 small fw-semibold">Total Pendapatan</p><h5 class="fw-bold mb-0">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h5></div>
            <i class="bi bi-cash-stack fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold border-0 pt-3 d-flex justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>Pesanan Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline-warning">Lihat Semua</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr><th>No. Pesanan</th><th>Customer</th><th>Total</th><th>Status</th><th>Tanggal</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td class="small fw-semibold"><?= htmlspecialchars($o['midtrans_order_id']) ?></td>
                  <td><?= htmlspecialchars($o['user_name']) ?></td>
                  <td class="text-success fw-semibold">Rp <?= number_format($o['total_price'], 0, ',', '.') ?></td>
                  <td><span class="badge bg-<?= $statusBadge[$o['status']] ?>">
                    <?= $statusLabel[$o['status']] ?></span></td>
                  <td class="small text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
