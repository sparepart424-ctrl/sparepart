<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';
requireAdmin();

$users = db()->query("
    SELECT u.*, COUNT(o.id) as order_count
    FROM users u LEFT JOIN orders o ON u.id=o.user_id
    WHERE u.role='user'
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users - Admin</title>
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
    <h4 class="fw-bold mb-4"><i class="bi bi-people me-2 text-warning"></i>Daftar Users</h4>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>#</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Pesanan</th><th>Bergabung</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                <td><span class="badge bg-info text-dark"><?= $u['order_count'] ?> pesanan</span></td>
                <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada user terdaftar.</td></tr>
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
