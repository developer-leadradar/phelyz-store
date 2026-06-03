<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireAdmin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    redirect('products.php');
}

$db = getDB();

// Get product info before deleting
$product = getProductById($productId);

if (!$product) {
    redirect('products.php');
}

// Check if product has orders
$hasOrders = $db->fetchOne(
    "SELECT COUNT(*) as total FROM order_items WHERE product_id = ?",
    [$productId]
)['total'];

if ($hasOrders > 0) {
    // Don't delete if has orders, just deactivate
    $db->update('products', ['is_active' => 0], 'id = ?', [$productId]);
    $_SESSION['message'] = 'Product has orders and has been deactivated instead of deleted';
    redirect('products.php?success=3');
} else {
    // Safe to delete
    $deleted = $db->delete('products', 'id = ?', [$productId]);
    
    if ($deleted) {
        redirect('products.php?success=3');
    } else {
        redirect('products.php?error=delete_failed');
    }
}
?>