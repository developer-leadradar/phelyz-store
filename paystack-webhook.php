<?php
/**
 * Paystack webhook receiver.
 *
 * Paystack POSTs a signed event here (charge.success, etc). This is the
 * reliable source of truth for payment status — the browser callback can be
 * abandoned mid-redirect, but the webhook always fires server-to-server.
 *
 * Signature: HMAC-SHA512 of the raw body using your SECRET key, sent in the
 * x-paystack-signature header. We reject anything that doesn't match.
 *
 * Set this URL in Paystack Dashboard → Settings → API Keys & Webhooks:
 *   https://<your-domain>/paystack-webhook.php
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/paystack.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$raw    = file_get_contents('php://input');
$secret = paystackSecretKey();
$sig    = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Verify signature
if (empty($secret) || empty($sig) || !hash_equals(hash_hmac('sha512', $raw, $secret), $sig)) {
    http_response_code(401);
    exit;
}

$event = json_decode($raw, true);
$type  = $event['event'] ?? '';
$data  = $event['data']  ?? [];

// Acknowledge immediately so Paystack doesn't retry; we've already validated.
http_response_code(200);

if ($type !== 'charge.success') {
    exit; // we only act on successful charges
}

$reference = $data['reference'] ?? '';
if ($reference === '') exit;

$db = getDB();
$order = $db->fetchOne("SELECT * FROM orders WHERE payment_reference = ?", [$reference]);

// Fallback: some events carry the order id in metadata
if (!$order && !empty($data['metadata']['order_id'])) {
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [(int)$data['metadata']['order_id']]);
}
if (!$order) exit;

// Already reconciled?
if (($order['payment_status'] ?? 'pending') === 'paid') exit;

$paidNgn = isset($data['amount']) ? ((float)$data['amount'] / 100) : 0.0;
if (($data['status'] ?? '') === 'success' && $paidNgn >= ((float)$order['total'] - 1)) {
    $db->update('orders', [
        'payment_status'    => 'paid',
        'status'            => 'processing',
        'payment_reference' => $reference,
    ], 'id = ?', [(int)$order['id']]);
    reduceStockForOrder((int)$order['id']);
}
exit;
