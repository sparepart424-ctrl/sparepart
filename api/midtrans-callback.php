<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) exit;

// Verify signature
$signatureKey = hash('sha512',
    $data['order_id'] .
    $data['status_code'] .
    $data['gross_amount'] .
    MIDTRANS_SERVER_KEY
);
if ($signatureKey !== $data['signature_key']) {
    http_response_code(403);
    exit;
}

$orderId      = $data['order_id'];
$transStatus  = $data['transaction_status'];
$fraudStatus  = $data['fraud_status'] ?? 'accept';

$statusMap = [
    'capture'   => ($fraudStatus === 'accept' ? 'paid' : 'cancelled'),
    'settlement'=> 'paid',
    'pending'   => 'pending',
    'deny'      => 'cancelled',
    'expire'    => 'cancelled',
    'cancel'    => 'cancelled',
];

$newStatus = $statusMap[$transStatus] ?? null;
if ($newStatus) {
    $stmt = db()->prepare("UPDATE orders SET status=? WHERE midtrans_order_id=?");
    $stmt->bind_param('ss', $newStatus, $orderId);
    $stmt->execute();
}

http_response_code(200);
echo 'OK';
