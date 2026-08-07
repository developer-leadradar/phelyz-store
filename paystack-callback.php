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
require_once __DIR__ . '/includes/cart-functions.php';
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
    // Claim the order atomically. The webhook may be racing us with the same
    // news, and only one of us may reduce stock, bank the coupon, issue a
    // tracking number and send the confirmation email.
    $claim = $db->query(
        "UPDATE orders SET payment_status = 'paid', status = 'processing', payment_reference = ?
         WHERE id = ? AND payment_status <> 'paid'",
        [$reference, $orderId]
    );

    if ($claim && $claim->rowCount() > 0) {
        finaliseOrderAfterPayment($orderId);
    } else {
        // The webhook got there first. It could not touch this shopper's
        // session, so clear the cart here instead.
        clearCart();
        couponSessionClear();
        unset($_SESSION['phelyz_pending_order']);
    }

    redirect('order-details.php?id=' . $orderId . '&success=1');
}

// Payment failed, abandoned, or amount mismatch. Cancel the reservation but
// leave the cart and any applied coupon exactly as they were, so the customer
// lands back on a full cart and can simply try again.
$db->update('orders', [
    'payment_status'    => 'failed',
    'status'            => 'cancelled',
    'payment_reference' => $reference,
], 'id = ?', [$orderId]);
unset($_SESSION['phelyz_pending_order']);
redirect('cart.php?payment=failed');
