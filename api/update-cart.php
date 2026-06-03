<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Clear cart action
if (isset($data['action']) && $data['action'] === 'clear') {
    clearCart();
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
    exit;
}

$itemId = isset($data['item_id']) ? (int)$data['item_id']
        : (isset($data['cart_item_id']) ? (int)$data['cart_item_id'] : null);

if ($itemId === null || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}
$quantity = (int)$data['quantity'];

if ($quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

$result = updateCartQuantity($itemId, $quantity);

if ($result) {
    $cartCount = getCartCount();
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated',
        'cart_count' => $cartCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?>