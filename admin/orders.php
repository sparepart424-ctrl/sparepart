<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    $newStatus = $_POST['status'];
    $oid       = (int)$_POST['id'];
    $allowed   = ['pending','paid','processing','shipped','completed','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $stmt = db()->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param('si', $newStatus, $oid);
        $stmt->execute();
        $msg = 'Status pesanan berhasil diperbarui.';
    }
}

$filter  = $_GET['status'] ?? '';
$where   = $filter ? "WHERE o.status = '" . db()->real_escape_string($filter) . "'" : '';
$orders  = db()->query("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o JOIN users u ON o.user_id=u.id
    $where ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusBadge = ['pending'=>'warning','paid'=>'success','processing'=>'info','shipped'=>'primary','completed'=>'success','cancelled'=>'danger'];
$statusLabel = ['pending'=>'Menunggu','paid'=>'Dibayar','processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Batal'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pesanan - Admin</title>
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
    <h4 class="fw-bold mb-4"><i class="bi bi-bag-check me-2 text-warning"></i>Kelola Pesanan</h4>
    <?php if ($msg): ?>
      <div class="alert alert-success py-2"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="mb-3 d-flex gap-2 flex-wrap">
      <?php $statuses = ['' => 'Semua'] + $statusLabel; ?>
      <?php foreach ($statuses as $key => $label): ?>
        <a href="?status=<?= $key ?>"
           class="btn btn-sm <?= $filter === $key ? 'btn-dark' : 'btn-outline-secondary' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>No. Pesanan</th><th>Customer</th><th>Total</th><th>Status</th><th>Tanggal</th><th class="text-center">Ubah Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td class="small fw-semibold"><?= htmlspecialchars($o['midtrans_order_id']) ?></td>
                <td>
                  <div><?= htmlspecialchars($o['user_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($o['user_email']) ?></small>
                </td>
                <td class="text-success fw-semibold">Rp <?= number_format($o['total_price'], 0, ',', '.') ?></td>
                <td><span class="badge bg-<?= $statusBadge[$o['status']] ?>">
                  <?= $statusLabel[$o['status']] ?></span></td>
                <td class="small text-muted"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                <td class="text-center">
                  <form method="POST" class="d-flex gap-1 justify-content-center">
                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                    <select name="status" class="form-select form-select-sm" style="width:130px">
                      <?php foreach ($statusLabel as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $o['status'] === $val ? 'selected' : '' ?>>
                          <?= $lbl ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="bi bi-check-lg"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pesanan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
