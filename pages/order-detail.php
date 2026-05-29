<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireLogin();

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header('Location: ' . BASE_URL . '/pages/orders.php'); exit; }

$orderItems = db()->query("
    SELECT oi.*, p.name, p.image FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $id
")->fetch_all(MYSQLI_ASSOC);

$statusBadge = ['pending'=>'warning','paid'=>'success','processing'=>'info','shipped'=>'primary','completed'=>'success','cancelled'=>'danger'];
$statusLabel = ['pending'=>'Menunggu Pembayaran','paid'=>'Dibayar','processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail Pesanan - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-5">
  <a href="<?= BASE_URL ?>/pages/orders.php" class="btn btn-sm btn-outline-secondary mb-4">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold border-0 pt-3">
          <i class="bi bi-box-seam me-2"></i>Produk Dipesan
        </div>
        <div class="card-body p-0">
          <?php foreach ($orderItems as $item): ?>
            <div class="d-flex align-items-center p-3 border-bottom">
              <div class="me-3">
                <?php if ($item['image'] && file_exists(__DIR__ . '/../assets/uploads/products/' . $item['image'])): ?>
                  <img src="<?= BASE_URL ?>/assets/uploads/products/<?= htmlspecialchars($item['image']) ?>"
                       style="width:60px;height:60px;object-fit:cover;border-radius:8px" alt="">
                <?php else: ?>
                  <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px">
                    <i class="bi bi-image text-secondary"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
              </div>
              <div class="fw-bold">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></div>
            </div>
          <?php endforeach; ?>
          <div class="p-3 d-flex justify-content-between fw-bold fs-6">
            <span>Total</span>
            <span class="text-success">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Info Pesanan</h6>
          <table class="table table-sm table-borderless small">
            <tr><td class="text-muted">No. Pesanan</td><td class="fw-semibold"><?= htmlspecialchars($order['midtrans_order_id']) ?></td></tr>
            <tr><td class="text-muted">Tanggal</td><td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td></tr>
            <tr><td class="text-muted">Status</td>
              <td><span class="badge bg-<?= $statusBadge[$order['status']] ?? 'secondary' ?>">
                <?= $statusLabel[$order['status']] ?? ucfirst($order['status']) ?>
              </span></td>
            </tr>
          </table>
          <hr>
          <h6 class="fw-bold mb-2">Alamat Pengiriman</h6>
          <p class="small text-muted"><?= nl2br(htmlspecialchars($order['shipping_address'] ?? '-')) ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
