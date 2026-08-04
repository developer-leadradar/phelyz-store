<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? 'apply';
$state  = isset($_POST['state']) ? sanitize($_POST['state']) : null;

if ($action === 'remove') {
    couponSessionClear();
    $summary = getCartSummary($state);
    echo json_encode([
        'success'  => true,
        'message'  => 'Code removed.',
        'applied'  => false,
        'totals'   => couponTotalsPayload($summary),
    ]);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Enter a code first.']);
    exit;
}

// Validate against the cart as it stands right now.
$summary = getCartSummary($state);
if (empty($summary['items'])) {
    echo json_encode(['success' => false, 'message' => 'Your bag is empty.']);
    exit;
}

$coupon = couponFind($code);
$check  = couponValidate($coupon, $summary['items'], $summary['subtotal'], $summary['shipping']);

if (!$check['ok']) {
    echo json_encode(['success' => false, 'message' => $check['message']]);
    exit;
}

couponSessionSet($code);
$summary = getCartSummary($state);   // recompute with the code attached

echo json_encode([
    'success' => true,
    'message' => $check['message'],
    'applied' => true,
    'code'    => $code,
    'totals'  => couponTotalsPayload($summary),
]);

/** The numbers the checkout page needs to redraw its summary. */
function couponTotalsPayload($s) {
    return [
        'subtotal'         => formatPrice($s['subtotal']),
        'discount'         => formatPrice($s['discount'] ?? 0),
        'discount_raw'     => (float)($s['discount'] ?? 0),
        'shipping'         => ((float)$s['shipping'] === 0.0) ? 'FREE' : formatPrice($s['shipping']),
        'shipping_is_free' => ((float)$s['shipping'] === 0.0),
        'total'            => formatPrice($s['total']),
        'coupon_code'      => $s['coupon_code'] ?? '',
    ];
}
