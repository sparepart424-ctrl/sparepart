<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireLogin();

$uid    = $_SESSION['user_id'];
$status = $_GET['status'] ?? '';

$orders = db()->query("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $uid
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusBadge = [
    'pending'    => 'warning',
    'paid'       => 'success',
    'processing' => 'info',
    'shipped'    => 'primary',
    'completed'  => 'success',
    'cancelled'  => 'danger',
];
$statusLabel = [
    'pending'    => 'Menunggu Pembayaran',
    'paid'       => 'Dibayar',
    'processing' => 'Diproses',
    'shipped'    => 'Dikirim',
    'completed'  => 'Selesai',
    'cancelled'  => 'Dibatalkan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pesanan Saya - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
  <?php if ($status === 'success'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Pembayaran berhasil! Pesanan Anda sedang diproses.</div>
  <?php elseif ($status === 'pending'): ?>
    <div class="alert alert-warning"><i class="bi bi-clock me-2"></i>Pembayaran Anda sedang diproses. Mohon selesaikan pembayaran.</div>
  <?php endif; ?>

  <h4 class="fw-bold mb-4"><i class="bi bi-bag-check me-2 text-warning"></i>Pesanan Saya</h4>

  <?php if (empty($orders)): ?>
    <div class="text-center py-5">
      <i class="bi bi-bag-x display-1 text-secondary"></i>
      <p class="mt-3 text-secondary fs-5">Belum ada pesanan.</p>
      <a href="<?= BASE_URL ?>" class="btn btn-warning btn-lg fw-bold">
        <i class="bi bi-shop me-2"></i>Mulai Belanja
      </a>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($orders as $o): ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-md-3">
                  <small class="text-muted">No. Pesanan</small>
                  <p class="fw-bold mb-0 small"><?= htmlspecialchars($o['midtrans_order_id']) ?></p>
                  <small class="text-muted"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></small>
                </div>
                <div class="col-md-2 mt-2 mt-md-0">
                  <small class="text-muted"><?= $o['item_count'] ?> produk</small>
                </div>
                <div class="col-md-3 mt-2 mt-md-0">
                  <span class="fw-bold text-success">Rp <?= number_format($o['total_price'], 0, ',', '.') ?></span>
                </div>
                <div class="col-md-2 mt-2 mt-md-0">
                  <span class="badge bg-<?= $statusBadge[$o['status']] ?? 'secondary' ?> px-3 py-2">
                    <?= $statusLabel[$o['status']] ?? ucfirst($o['status']) ?>
                  </span>
                </div>
                <div class="col-md-2 mt-2 mt-md-0 text-md-end">
                  <a href="<?= BASE_URL ?>/pages/order-detail.php?id=<?= $o['id'] ?>"
                     class="btn btn-sm btn-outline-warning">Detail</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
