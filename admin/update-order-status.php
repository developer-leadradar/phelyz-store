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
        $message = phelyzEmailTemplate(
            '<p style="margin:0 0 12px;font-size:16px;">Your order has an update.</p>'
          . '<p style="margin:0 0 18px;color:#44403C;">Order <strong>' . htmlspecialchars($order['order_number'] ?? '') . '</strong> is now <strong>' . htmlspecialchars(ucfirst($newStatus)) . '</strong>.</p>'
          . phelyzEmailButton('Track My Order', SITE_URL . '/track.php')
          . '<p style="margin:0;color:#78716C;font-size:13px;">Thank you for shopping with Phelyz Store.</p>',
            'Order ' . $order['order_number'] . ' is now ' . ucfirst($newStatus) . '.'
        );
        
        emailContext(['category'=>'transactional','source_type'=>'order_status','source_id'=>$order['order_number']]);
        sendEmail($user['email'], $subject, $message);
    }
    
    redirect('order-details.php?id=' . $orderId . '&success=status_updated');
} else {
    redirect('order-details.php?id=' . $orderId . '&error=update_failed');
}
?>