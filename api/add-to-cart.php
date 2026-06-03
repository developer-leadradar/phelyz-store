<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Check if product exists
$product = getProductById($productId);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check stock
if ($product['stock_quantity'] < $quantity) {
    echo json_encode([
        'success' => false,
        'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' available'
    ]);
    exit;
}

// Add to cart
$result = addToCart($productId, $quantity);

if ($result) {
    $cartCount = getCartCount();
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => $cartCount
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add product to cart'
    ]);
}
?>