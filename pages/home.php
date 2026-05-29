<?php
// Redirect ke index.php karena halaman produk ada di sana
require_once __DIR__ . '/../config/database.php';
header('Location: ' . BASE_URL);
exit;
