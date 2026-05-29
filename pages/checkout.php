<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireLogin();

$uid   = $_SESSION['user_id'];
$items = db()->query("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock
    FROM cart c JOIN products p ON c.product_id=p.id
    WHERE c.user_id=$uid
")->fetch_all(MYSQLI_ASSOC);

if (empty($items)) { header('Location: ' . BASE_URL . '/pages/cart.php'); exit; }

$user = db()->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$total = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));

$snapToken  = '';
$orderId    = '';
$snapError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    if (!$address) { $snapError = 'Alamat pengiriman wajib diisi.'; goto render; }

    // Create order
    $orderId = 'ORD-' . time() . '-' . $uid;
    $stmt = db()->prepare("INSERT INTO orders (user_id, total_price, midtrans_order_id, shipping_address) VALUES (?,?,?,?)");
    $stmt->bind_param('idss', $uid, $total, $orderId, $address);
    $stmt->execute();
    $dbOrderId = db()->insert_id;

    // Insert order items & reduce stock
    foreach ($items as $item) {
        $stmt = db()->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
        $stmt->bind_param('iiid', $dbOrderId, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
        db()->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
    }

    // Clear cart
    db()->query("DELETE FROM cart WHERE user_id=$uid");

    // Build Midtrans params
    $itemDetails = array_map(fn($i) => [
        'id'       => (string)$i['product_id'],
        'price'    => (int)$i['price'],
        'quantity' => $i['quantity'],
        'name'     => mb_strimwidth($i['name'], 0, 50, '...'),
    ], $items);

    $params = [
        'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int)$total],
        'customer_details'    => [
            'first_name' => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'] ?? '',
        ],
        'item_details'        => $itemDetails,
        'callbacks'           => [
            'finish' => BASE_URL . '/pages/orders.php?status=finish',
        ],
    ];

    $snapToken = midtransGetSnapToken($params);
    if ($snapToken) {
        $stmt = db()->prepare("UPDATE orders SET snap_token=? WHERE id=?");
        $stmt->bind_param('si', $snapToken, $dbOrderId);
        $stmt->execute();
    } else {
        $snapError = 'Gagal menghubungi payment gateway. Coba lagi.';
        // Revert order
        db()->query("DELETE FROM orders WHERE id=$dbOrderId");
        db()->query("INSERT INTO cart (user_id, product_id, quantity) " .
            implode(' UNION ALL ', array_map(fn($i) => "SELECT $uid,{$i['product_id']},{$i['quantity']}", $items)));
    }
}
render:
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
  <h4 class="fw-bold mb-4"><i class="bi bi-credit-card me-2 text-warning"></i>Checkout</h4>

  <?php if ($snapError): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $snapError ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <?php if (!$snapToken): ?>
      <div class="card border-0 shadow-sm p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Informasi Pemesan</h6>
        <p class="mb-1"><strong><?= htmlspecialchars($user['name']) ?></strong></p>
        <p class="mb-1 text-muted small"><?= htmlspecialchars($user['email']) ?></p>
        <p class="mb-0 text-muted small"><?= htmlspecialchars($user['phone'] ?? '-') ?></p>
      </div>
      <div class="card border-0 shadow-sm p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Alamat Pengiriman</h6>
        <form method="POST" id="checkoutForm">
          <div class="mb-3">
            <textarea name="address" class="form-control" rows="4"
              placeholder="Masukkan alamat lengkap pengiriman..." required><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-warning fw-bold w-100 btn-lg" id="btnCheckout">
            <i class="bi bi-lock me-2"></i>Bayar Sekarang — Rp <?= number_format($total, 0, ',', '.') ?>
          </button>
        </form>
      </div>
      <?php else: ?>
      <div class="card border-0 shadow-sm p-4 text-center">
        <i class="bi bi-shield-check display-3 text-success mb-3"></i>
        <h5 class="fw-bold">Pesanan Dibuat!</h5>
        <p class="text-muted">Klik tombol di bawah untuk melanjutkan pembayaran.</p>
        <button id="pay-button" class="btn btn-warning btn-lg fw-bold w-100">
          <i class="bi bi-credit-card me-2"></i>Lanjutkan Pembayaran
        </button>
        <a href="<?= BASE_URL ?>/pages/orders.php" class="btn btn-outline-secondary mt-2 w-100">
          Lihat Pesanan Saya
        </a>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold border-0 pt-3">
          <i class="bi bi-bag me-2"></i>Ringkasan Produk
        </div>
        <div class="card-body">
          <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between mb-2 small">
              <span><?= htmlspecialchars(mb_strimwidth($item['name'], 0, 30, '...')) ?>
                <span class="text-muted">x<?= $item['quantity'] ?></span>
              </span>
              <span class="fw-semibold">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
            </div>
          <?php endforeach; ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-6">
            <span>Total</span>
            <span class="text-success">Rp <?= number_format($total, 0, ',', '.') ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($snapToken): ?>
<script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
<script>
document.getElementById('pay-button').addEventListener('click', function() {
  snap.pay('<?= $snapToken ?>', {
    onSuccess: function(result) {
      window.location.href = '<?= BASE_URL ?>/pages/orders.php?status=success';
    },
    onPending: function(result) {
      window.location.href = '<?= BASE_URL ?>/pages/orders.php?status=pending';
    },
    onError: function(result) {
      alert('Pembayaran gagal. Silakan coba lagi.');
    },
    onClose: function() {
      window.location.href = '<?= BASE_URL ?>/pages/orders.php';
    }
  });
});
// Auto-open payment popup
window.addEventListener('load', () => document.getElementById('pay-button').click());
</script>
<?php endif; ?>
<script>
document.getElementById('checkoutForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('btnCheckout');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>
</body>
</html>
