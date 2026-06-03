<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireAdmin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$newStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';

if (!$orderId || !$newStatus) {
    redirect('orders.php');
}

$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if (!in_array($newStatus, $validStatuses)) {
    redirect('orders.php');
}

$db = getDB();

// Get order
$order = getOrderById($orderId);

if (!$order) {
    redirect('orders.php');
}

// Update status
$updated = updateOrderStatus($orderId, $newStatus);

if ($updated) {
    // Send email notification to customer
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$order['user_id']]);
    
    if ($user) {
        $subject = "Order Status Update - " . $order['order_number'];
        $message = "
        <html>
        <body>
            <h2>Order Status Updated</h2>
            <p>Your order <strong>{$order['order_number']}</strong> status has been updated to: <strong>" . ucfirst($newStatus) . "</strong></p>
            <p>Thank you for shopping with Phelyz Diamond Store!</p>
        </body>
        </html>
        ";
        
        sendEmail($user['email'], $subject, $message);
    }
    
    redirect('order-details.php?id=' . $orderId . '&success=status_updated');
} else {
    redirect('order-details.php?id=' . $orderId . '&error=update_failed');
}
?>