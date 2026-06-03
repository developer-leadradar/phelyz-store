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

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$productId = (int)$data['product_id'];
$action = isset($data['action']) ? $data['action'] : 'add';

if ($action === 'remove') {
    $result = removeFromWishlist($productId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Removed from wishlist'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove from wishlist'
        ]);
    }
} else {
    $result = addToWishlist($productId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Added to wishlist'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add to wishlist'
        ]);
    }
}
?>