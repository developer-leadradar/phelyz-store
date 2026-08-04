<?php
/**
 * Paystack return URL. The customer lands here after paying (or cancelling).
 * Paystack appends ?reference=… (and trxref=…) to the callback we supplied,
 * which already carries ?order=<id>.
 *
 * We NEVER trust the redirect alone - the transaction is verified against
 * Paystack's API and the paid amount is checked against the order total.
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/paystack.php';

$orderId   = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$reference = trim($_GET['reference'] ?? ($_GET['trxref'] ?? ''));

if ($orderId <= 0 || $reference === '') {
    redirect('index.php');
}

$db    = getDB();
$order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
if (!$order) {
    redirect('index.php');
}

// Already reconciled? Don't verify twice.
if (($order['payment_status'] ?? 'pending') === 'paid') {
    redirect('order-details.php?id=' . $orderId . '&success=1');
}

$result = paystackVerify($reference);

if ($result['ok'] && $result['paid'] && $result['amount_ngn'] >= ((float)$order['total'] - 1)) {
    $db->update('orders', [
        'payment_status'    => 'paid',
        'status'            => 'processing',
        'payment_reference' => $reference,
    ], 'id = ?', [$orderId]);
    // Now that payment is confirmed, reduce stock (idempotent)
    reduceStockForOrder($orderId);
    redirect('order-details.php?id=' . $orderId . '&success=1');
}

// Payment failed, abandoned, or amount mismatch
$db->update('orders', [
    'payment_status'    => 'failed',
    'payment_reference' => $reference,
], 'id = ?', [$orderId]);
redirect('order-details.php?id=' . $orderId . '&payment=failed');
