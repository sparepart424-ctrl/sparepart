<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth-guard.php';

// Fetch products with category
$search   = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);

$where = ['1=1'];
$types = '';
$vals  = [];

if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $types  .= 'ss';
    $like    = "%$search%";
    $vals[]  = $like;
    $vals[]  = $like;
}
if ($category) {
    $where[]  = 'p.category_id = ?';
    $types   .= 'i';
    $vals[]   = $category;
}

$sql  = "SELECT p.*, c.name AS category_name FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC";
$stmt = db()->prepare($sql);
if ($types) $stmt->bind_param($types, ...$vals);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = db()->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SparePart Store - Toko Sparepart Terpercaya</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    .hero { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
    .product-card { transition: transform .2s, box-shadow .2s; border: none; border-radius: 12px; overflow: hidden; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,.15) !important; }
    .product-img { height: 200px; object-fit: cover; width: 100%; }
    .product-img-placeholder { height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
    .badge-category { font-size: .7rem; }
    .btn-cart { border-radius: 8px; }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero -->
<section class="hero py-5 text-white">
  <div class="container text-center py-3">
    <h1 class="display-5 fw-bold mb-3">
      <i class="bi bi-gear-fill text-warning me-2"></i>SparePart Store
    </h1>
    <p class="lead text-light mb-4">Temukan sparepart berkualitas untuk kendaraan Anda</p>
    <form method="GET" class="row g-2 justify-content-center">
      <div class="col-md-5">
        <input type="text" name="search" class="form-control form-control-lg" placeholder="Cari produk..."
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select form-select-lg">
          <option value="">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </form>
  </div>
</section>

<!-- Products -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="fw-bold mb-0">
        <?= $search || $category ? 'Hasil Pencarian' : 'Semua Produk' ?>
        <span class="badge bg-warning text-dark ms-2"><?= count($products) ?></span>
      </h5>
      <?php if ($search || $category): ?>
        <a href="<?= BASE_URL ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i>Reset
        </a>
      <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
      <div class="text-center py-5">
        <i class="bi bi-search display-1 text-secondary"></i>
        <p class="mt-3 text-secondary">Produk tidak ditemukan.</p>
        <a href="<?= BASE_URL ?>" class="btn btn-warning">Lihat Semua Produk</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($products as $p): ?>
          <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card product-card shadow-sm h-100">
              <?php if ($p['image'] && file_exists(__DIR__ . '/assets/uploads/products/' . $p['image'])): ?>
                <img src="<?= BASE_URL ?>/assets/uploads/products/<?= htmlspecialchars($p['image']) ?>"
                     class="product-img" alt="<?= htmlspecialchars($p['name']) ?>">
              <?php else: ?>
                <div class="product-img-placeholder">
                  <i class="bi bi-image text-secondary" style="font-size:3rem"></i>
                </div>
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <?php if ($p['category_name']): ?>
                  <span class="badge bg-secondary badge-category mb-2 align-self-start">
                    <?= htmlspecialchars($p['category_name']) ?>
                  </span>
                <?php endif; ?>
                <h6 class="card-title fw-bold"><?= htmlspecialchars($p['name']) ?></h6>
                <p class="card-text small text-muted flex-grow-1">
                  <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 60, '...')) ?>
                </p>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="fw-bold text-success fs-6">
                    Rp <?= number_format($p['price'], 0, ',', '.') ?>
                  </span>
                  <small class="text-muted">Stok: <?= $p['stock'] ?></small>
                </div>
              </div>
              <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                <div class="d-grid gap-2">
                  <a href="<?= BASE_URL ?>/pages/product-detail.php?id=<?= $p['id'] ?>"
                     class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eye me-1"></i>Detail
                  </a>
                  <?php if ($p['stock'] > 0): ?>
                    <button class="btn btn-warning btn-sm btn-cart fw-semibold"
                            onclick="addToCart(<?= $p['id'] ?>, this)">
                      <i class="bi bi-cart-plus me-1"></i>Masukkan Keranjang
                    </button>
                  <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>Stok Habis</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addToCart(productId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menambahkan...';

  fetch('<?= BASE_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'add', product_id: productId})
  })
  .then(r => r.json())
  .then(data => {
    if (data.redirect) {
      window.location.href = data.redirect;
      return;
    }
    if (data.success) {
      btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Ditambahkan!';
      btn.classList.replace('btn-warning', 'btn-success');
      // update cart badge
      const badge = document.querySelector('.navbar .bi-cart3')?.closest('a')?.querySelector('.badge');
      if (badge) badge.textContent = data.cart_count;
      else {
        const cartLink = document.querySelector('.navbar .bi-cart3')?.closest('a');
        if (cartLink) {
          cartLink.insertAdjacentHTML('beforeend',
            `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">${data.cart_count}</span>`);
        }
      }
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Masukkan Keranjang';
        btn.classList.replace('btn-success', 'btn-warning');
        btn.disabled = false;
      }, 2000);
    }
  })
  .catch(() => {
    btn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Masukkan Keranjang';
    btn.disabled = false;
  });
}
</script>
</body>
</html>
