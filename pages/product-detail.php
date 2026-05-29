<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { header('Location: ' . BASE_URL); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($p['name']) ?> - SparePart Store</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Beranda</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($p['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <div class="col-md-5">
      <?php if ($p['image'] && file_exists(__DIR__ . '/../assets/uploads/products/' . $p['image'])): ?>
        <img src="<?= BASE_URL ?>/assets/uploads/products/<?= htmlspecialchars($p['image']) ?>"
             class="img-fluid rounded-3 shadow" style="width:100%;max-height:400px;object-fit:cover"
             alt="<?= htmlspecialchars($p['name']) ?>">
      <?php else: ?>
        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="height:350px">
          <i class="bi bi-image text-secondary" style="font-size:5rem"></i>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-7">
      <?php if ($p['category_name']): ?>
        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($p['category_name']) ?></span>
      <?php endif; ?>
      <h2 class="fw-bold"><?= htmlspecialchars($p['name']) ?></h2>
      <h3 class="text-success fw-bold my-3">Rp <?= number_format($p['price'], 0, ',', '.') ?></h3>
      <p class="text-muted"><?= nl2br(htmlspecialchars($p['description'] ?? '')) ?></p>
      <div class="d-flex align-items-center gap-3 mb-4">
        <span class="<?= $p['stock'] > 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
          <i class="bi bi-<?= $p['stock'] > 0 ? 'check-circle' : 'x-circle' ?> me-1"></i>
          <?= $p['stock'] > 0 ? "Stok: {$p['stock']}" : 'Stok Habis' ?>
        </span>
      </div>
      <?php if ($p['stock'] > 0): ?>
        <div class="d-flex gap-3 align-items-center mb-3">
          <div class="input-group" style="width:130px">
            <button class="btn btn-outline-secondary" id="btnMinus" type="button">-</button>
            <input type="number" id="qty" class="form-control text-center" value="1" min="1" max="<?= $p['stock'] ?>">
            <button class="btn btn-outline-secondary" id="btnPlus" type="button">+</button>
          </div>
        </div>
        <button class="btn btn-warning btn-lg fw-bold px-5" id="btnAddCart">
          <i class="bi bi-cart-plus me-2"></i>Masukkan Keranjang
        </button>
      <?php else: ?>
        <button class="btn btn-secondary btn-lg" disabled>Stok Habis</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const maxStock = <?= $p['stock'] ?>;
const qtyInput = document.getElementById('qty');
document.getElementById('btnMinus')?.addEventListener('click', () => {
  if (+qtyInput.value > 1) qtyInput.value--;
});
document.getElementById('btnPlus')?.addEventListener('click', () => {
  if (+qtyInput.value < maxStock) qtyInput.value++;
});
document.getElementById('btnAddCart')?.addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menambahkan...';
  fetch('<?= BASE_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'add', product_id: <?= $p['id'] ?>, quantity: +qtyInput.value})
  })
  .then(r => r.json())
  .then(data => {
    if (data.redirect) { window.location.href = data.redirect; return; }
    if (data.success) {
      this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Berhasil Ditambahkan!';
      this.classList.replace('btn-warning', 'btn-success');
      setTimeout(() => window.location.href = '<?= BASE_URL ?>/pages/cart.php', 1200);
    }
  });
});
</script>
</body>
</html>
