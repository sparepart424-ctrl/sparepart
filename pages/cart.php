<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireLogin();

$uid   = $_SESSION['user_id'];
$items = db()->query("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $uid
    ORDER BY c.id DESC
")->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Keranjang - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
  <h4 class="fw-bold mb-4"><i class="bi bi-cart3 me-2 text-warning"></i>Keranjang Belanja</h4>

  <?php if (empty($items)): ?>
    <div class="text-center py-5">
      <i class="bi bi-cart-x display-1 text-secondary"></i>
      <p class="mt-3 text-secondary fs-5">Keranjang Anda masih kosong.</p>
      <a href="<?= BASE_URL ?>" class="btn btn-warning btn-lg fw-bold">
        <i class="bi bi-shop me-2"></i>Mulai Belanja
      </a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <?php foreach ($items as $i): ?>
              <div class="d-flex align-items-center p-3 border-bottom cart-row" id="row-<?= $i['cart_id'] ?>">
                <div class="me-3" style="width:70px;height:70px;flex-shrink:0">
                  <?php if ($i['image'] && file_exists(__DIR__ . '/../assets/uploads/products/' . $i['image'])): ?>
                    <img src="<?= BASE_URL ?>/assets/uploads/products/<?= htmlspecialchars($i['image']) ?>"
                         style="width:70px;height:70px;object-fit:cover;border-radius:8px" alt="">
                  <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width:70px;height:70px">
                      <i class="bi bi-image text-secondary fs-4"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($i['name']) ?></h6>
                  <small class="text-success fw-bold">Rp <?= number_format($i['price'], 0, ',', '.') ?></small>
                </div>
                <div class="d-flex align-items-center gap-2 mx-3">
                  <button class="btn btn-outline-secondary btn-sm" onclick="updateQty(<?= $i['cart_id'] ?>, -1)">-</button>
                  <span class="fw-bold qty-display" style="min-width:24px;text-align:center"><?= $i['quantity'] ?></span>
                  <button class="btn btn-outline-secondary btn-sm" onclick="updateQty(<?= $i['cart_id'] ?>, 1, <?= $i['stock'] ?>)">+</button>
                </div>
                <div class="text-end" style="min-width:110px">
                  <div class="fw-bold subtotal">Rp <?= number_format($i['quantity'] * $i['price'], 0, ',', '.') ?></div>
                </div>
                <button class="btn btn-link text-danger ms-2" onclick="removeItem(<?= $i['cart_id'] ?>)">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Subtotal (<?= count($items) ?> produk)</span>
              <span class="fw-bold" id="cart-total">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
              <span class="fw-bold fs-5">Total</span>
              <span class="fw-bold fs-5 text-success" id="cart-total-2">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
            <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-warning w-100 fw-bold btn-lg">
              <i class="bi bi-credit-card me-2"></i>Checkout Sekarang
            </a>
            <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary w-100 mt-2">
              <i class="bi bi-arrow-left me-1"></i>Lanjut Belanja
            </a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateQty(cartId, delta, maxStock = 999) {
  const row      = document.getElementById('row-' + cartId);
  const qtyEl    = row.querySelector('.qty-display');
  const newQty   = Math.max(1, Math.min(maxStock, +qtyEl.textContent + delta));
  fetch('<?= BASE_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'update', cart_id: cartId, quantity: newQty})
  }).then(r => r.json()).then(data => {
    if (data.success) {
      qtyEl.textContent = newQty;
      row.querySelector('.subtotal').textContent = data.subtotal;
      document.getElementById('cart-total').textContent  = data.total;
      document.getElementById('cart-total-2').textContent = data.total;
    }
  });
}

function removeItem(cartId) {
  if (!confirm('Hapus produk ini dari keranjang?')) return;
  fetch('<?= BASE_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'remove', cart_id: cartId})
  }).then(r => r.json()).then(data => {
    if (data.success) {
      document.getElementById('row-' + cartId).remove();
      document.getElementById('cart-total').textContent  = data.total;
      document.getElementById('cart-total-2').textContent = data.total;
      if (data.cart_count === 0) location.reload();
    }
  });
}
</script>
</body>
</html>
