<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireAdmin();

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'add') {
        if (!$name) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $stmt = db()->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $msg = 'Kategori berhasil ditambahkan.';
        }
    } elseif ($action === 'edit') {
        if (!$name) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $stmt = db()->prepare("UPDATE categories SET name=? WHERE id=?");
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $msg = 'Kategori berhasil diperbarui.';
        }
    } elseif ($action === 'delete') {
        db()->query("DELETE FROM categories WHERE id=$id");
        $msg = 'Kategori berhasil dihapus.';
    }
}

$categories = db()->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c LEFT JOIN products p ON c.id=p.category_id
    GROUP BY c.id ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kategori - Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    .sidebar { min-height: 100vh; background: #1a1a2e; }
    .sidebar .nav-link { color: rgba(255,255,255,.7); border-radius: 8px; margin-bottom: 4px; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,193,7,.15); color: #ffc107; }
  </style>
</head>
<body class="bg-light">
<div class="d-flex">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-warning"></i>Kelola Kategori</h4>
      <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#catModal">
        <i class="bi bi-plus-lg me-2"></i>Tambah Kategori
      </button>
    </div>
    <?php if ($msg): ?>
      <div class="alert alert-success py-2"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>Nama Kategori</th><th>Jumlah Produk</th><th class="text-center">Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                <td><span class="badge bg-secondary"><?= $cat['product_count'] ?> produk</span></td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openEdit(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
              <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada kategori.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="catModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="catModalTitle">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="catAction" value="add">
        <input type="hidden" name="id" id="catId" value="">
        <div class="modal-body">
          <label class="form-label fw-semibold">Nama Kategori</label>
          <input type="text" name="name" id="catName" class="form-control" placeholder="Nama kategori" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-2"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEdit(id, name) {
  document.getElementById('catModalTitle').textContent = 'Edit Kategori';
  document.getElementById('catAction').value = 'edit';
  document.getElementById('catId').value     = id;
  document.getElementById('catName').value   = name;
  new bootstrap.Modal(document.getElementById('catModal')).show();
}
document.getElementById('catModal').addEventListener('hidden.bs.modal', () => {
  document.getElementById('catModalTitle').textContent = 'Tambah Kategori';
  document.getElementById('catAction').value = 'add';
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
});
</script>
</body>
</html>
