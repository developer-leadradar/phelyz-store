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
$selectedColor = isset($data['selected_color']) ? trim((string)$data['selected_color']) : '';
if ($selectedColor === '') $selectedColor = null;

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

// If product has colour variants, a colour must be selected
$availableColors = parseProductColors($product['colors'] ?? '');
if (!empty($availableColors)) {
    $valid = false;
    foreach ($availableColors as $c) {
        if (strcasecmp($c['name'], (string)$selectedColor) === 0) {
            $selectedColor = $c['name']; // canonicalise
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        echo json_encode(['success' => false, 'message' => 'Please pick a valid colour']);
        exit;
    }
}

// Stock check (express bypasses; out_of_stock is rejected by addToCart)
$stockStatus = $product['stock_status'] ?? 'available';
if ($stockStatus !== 'express' && $stockStatus !== 'out_of_stock' && $product['stock_quantity'] < $quantity) {
    echo json_encode([
        'success' => false,
        'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' available'
    ]);
    exit;
}

// Add to cart
$result = addToCart($productId, $quantity, $selectedColor);

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