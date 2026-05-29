<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-guard.php';

header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_POST['action'] ?? '');

if (!isLoggedIn()) {
    echo json_encode(['redirect' => BASE_URL . '/auth/login.php']);
    exit;
}

$uid = $_SESSION['user_id'];
$db  = db();

function cartCount(int $uid): int {
    global $db;
    $r = $db->query("SELECT SUM(quantity) as t FROM cart WHERE user_id = $uid");
    return (int)($r->fetch_assoc()['t'] ?? 0);
}

switch ($action) {
    case 'add':
        $pid = (int)($input['product_id'] ?? 0);
        if (!$pid) { echo json_encode(['success' => false]); exit; }

        $r = $db->query("SELECT stock FROM products WHERE id = $pid");
        $product = $r->fetch_assoc();
        if (!$product || $product['stock'] < 1) {
            echo json_encode(['success' => false, 'message' => 'Stok habis']);
            exit;
        }

        $existing = $db->query("SELECT id, quantity FROM cart WHERE user_id=$uid AND product_id=$pid")->fetch_assoc();
        if ($existing) {
            $newQty = $existing['quantity'] + 1;
            if ($newQty > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Melebihi stok tersedia']);
                exit;
            }
            $db->query("UPDATE cart SET quantity=$newQty WHERE id={$existing['id']}");
        } else {
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->bind_param('ii', $uid, $pid);
            $stmt->execute();
        }
        echo json_encode(['success' => true, 'cart_count' => cartCount($uid)]);
        break;

    case 'update':
        $cartId = (int)($input['cart_id'] ?? 0);
        $qty    = (int)($input['quantity'] ?? 1);
        if ($qty < 1) $qty = 1;
        $db->query("UPDATE cart SET quantity=$qty WHERE id=$cartId AND user_id=$uid");
        // recalc subtotal
        $row = $db->query("SELECT c.quantity, p.price FROM cart c JOIN products p ON c.product_id=p.id WHERE c.id=$cartId")->fetch_assoc();
        $subtotal = $row ? $row['quantity'] * $row['price'] : 0;
        // total
        $total = $db->query("SELECT SUM(c.quantity * p.price) as t FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=$uid")->fetch_assoc()['t'] ?? 0;
        echo json_encode([
            'success'    => true,
            'subtotal'   => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            'total'      => 'Rp ' . number_format($total, 0, ',', '.'),
            'cart_count' => cartCount($uid),
        ]);
        break;

    case 'remove':
        $cartId = (int)($input['cart_id'] ?? 0);
        $db->query("DELETE FROM cart WHERE id=$cartId AND user_id=$uid");
        $total = $db->query("SELECT SUM(c.quantity * p.price) as t FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=$uid")->fetch_assoc()['t'] ?? 0;
        echo json_encode([
            'success'    => true,
            'total'      => 'Rp ' . number_format($total, 0, ',', '.'),
            'cart_count' => cartCount($uid),
        ]);
        break;

    default:
        echo json_encode(['success' => false]);
}
