<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireAdmin();

$msg   = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pid  = (int)$_POST['id'];
        $prod = db()->query("SELECT image FROM products WHERE id=$pid")->fetch_assoc();
        if ($prod && $prod['image']) {
            $imgPath = __DIR__ . '/../assets/uploads/products/' . $prod['image'];
            if (file_exists($imgPath)) unlink($imgPath);
        }
        db()->query("DELETE FROM products WHERE id=$pid");
        $msg = 'Produk berhasil dihapus.';
    }

    if (in_array($action, ['add', 'edit'])) {
        $name     = trim($_POST['name'] ?? '');
        $catId    = (int)($_POST['category_id'] ?? 0) ?: null;
        $desc     = trim($_POST['description'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $stock    = (int)($_POST['stock'] ?? 0);
        $pid      = (int)($_POST['id'] ?? 0);
        $imgName  = $_POST['current_image'] ?? null;

        if (!$name || !$price) {
            $error = 'Nama dan harga wajib diisi.';
        } else {
            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
                    goto render;
                }
                $newName = uniqid('prod_') . '.' . $ext;
                $dest    = __DIR__ . '/../assets/uploads/products/' . $newName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    // Remove old image
                    if ($imgName && file_exists(__DIR__ . '/../assets/uploads/products/' . $imgName)) {
                        unlink(__DIR__ . '/../assets/uploads/products/' . $imgName);
                    }
                    $imgName = $newName;
                }
            }

            if ($action === 'add') {
                $stmt = db()->prepare("INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('issdis', $catId, $name, $desc, $price, $stock, $imgName);
                $stmt->execute();
                $msg = 'Produk berhasil ditambahkan.';
            } else {
                $stmt = db()->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?");
                $stmt->bind_param('issdisi', $catId, $name, $desc, $price, $stock, $imgName, $pid);
                $stmt->execute();
                $msg = 'Produk berhasil diperbarui.';
            }
        }
    }
}
render:
$products   = db()->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$categories = db()->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editProduct = db()->query("SELECT * FROM products WHERE id=$eid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Produk - Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    .sidebar { min-height: 100vh; background: #1a1a2e; }
    .sidebar .nav-link { color: rgba(255,255,255,.7); border-radius: 8px; margin-bottom: 4px; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,193,7,.15); color: #ffc107; }
    .product-thumb { width:50px; height:50px; object-fit:cover; border-radius:6px; }
  </style>
</head>
<body class="bg-light">
<div class="d-flex">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-warning"></i>Kelola Produk</h4>
      <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#productModal">
        <i class="bi bi-plus-lg me-2"></i>Tambah Produk
      </button>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:60px">Foto</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td>
                    <?php if ($p['image'] && file_exists(__DIR__ . '/../assets/uploads/products/' . $p['image'])): ?>
                      <img src="<?= BASE_URL ?>/assets/uploads/products/<?= htmlspecialchars($p['image']) ?>"
                           class="product-thumb" alt="">
                    <?php else: ?>
                      <div class="product-thumb bg-light d-flex align-items-center justify-content-center">
                        <i class="bi bi-image text-secondary small"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($p['cat_name'] ?? '-') ?></span></td>
                  <td class="text-success fw-semibold">Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
                  <td>
                    <span class="badge <?= $p['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                      <?= $p['stock'] ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Hapus produk ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada produk.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Produk -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalTitle">Tambah Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">
        <input type="hidden" name="current_image" id="formCurrentImage" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
              <input type="text" name="name" id="formName" class="form-control" placeholder="Nama produk" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kategori</label>
              <select name="category_id" id="formCategory" class="form-select">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
              <input type="number" name="price" id="formPrice" class="form-control" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Stok</label>
              <input type="number" name="stock" id="formStock" class="form-control" min="0" value="0">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Deskripsi</label>
              <textarea name="description" id="formDesc" class="form-control" rows="3" placeholder="Deskripsi produk..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Foto Produk</label>
              <input type="file" name="image" id="formImage" class="form-control" accept="image/jpeg,image/png,image/webp">
              <div id="currentImagePreview" class="mt-2 d-none">
                <small class="text-muted">Foto saat ini:</small><br>
                <img id="previewImg" src="" style="max-height:100px;border-radius:8px;margin-top:4px">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning fw-bold" id="btnSubmit">
            <i class="bi bi-save me-2"></i>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('productModal'));

function openEdit(p) {
  document.getElementById('modalTitle').textContent = 'Edit Produk';
  document.getElementById('formAction').value    = 'edit';
  document.getElementById('formId').value         = p.id;
  document.getElementById('formName').value       = p.name;
  document.getElementById('formCategory').value   = p.category_id || '';
  document.getElementById('formPrice').value      = p.price;
  document.getElementById('formStock').value      = p.stock;
  document.getElementById('formDesc').value       = p.description || '';
  document.getElementById('formCurrentImage').value = p.image || '';
  document.getElementById('formImage').required   = false;

  const preview = document.getElementById('currentImagePreview');
  if (p.image) {
    preview.classList.remove('d-none');
    document.getElementById('previewImg').src = '<?= BASE_URL ?>/assets/uploads/products/' + p.image;
  } else {
    preview.classList.add('d-none');
  }
  modal.show();
}

document.getElementById('productModal').addEventListener('hidden.bs.modal', () => {
  document.getElementById('modalTitle').textContent = 'Tambah Produk';
  document.getElementById('productForm').reset();
  document.getElementById('formAction').value = 'add';
  document.getElementById('formId').value     = '';
  document.getElementById('formImage').required = false;
  document.getElementById('currentImagePreview').classList.add('d-none');
});
</script>
</body>
</html>
