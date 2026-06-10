<?php
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$state = isset($_GET['state']) ? sanitize($_GET['state']) : '';

if (empty($state)) {
    echo json_encode(['success' => false, 'message' => 'No state provided']);
    exit;
}

// Persist the choice for this session
$_SESSION['phelyz_shipping_state'] = $state;

$db  = getDB();
$row = $db->fetchOne("SELECT rate FROM shipping_rates WHERE state = ?", [$state]);

if ($row) {
    $rate = (float)$row['rate'];
} else {
    // Fallback: read from settings.json
    $settingsFile = __DIR__ . '/../data/settings.json';
    $rate = 2500.00;
    if (file_exists($settingsFile)) {
        $s = json_decode(file_get_contents($settingsFile), true);
        $rate = (float)($s['shipping_fee'] ?? 2500);
    }
}

// Check free-shipping threshold
$subtotal = 0;
$cartItems = getCartItems();
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$threshold = 50000.0;
$settingsFile = __DIR__ . '/../data/settings.json';
if (file_exists($settingsFile)) {
    $s = json_decode(file_get_contents($settingsFile), true);
    $threshold = (float)($s['free_shipping_threshold'] ?? 50000);
}

$isFree = $subtotal >= $threshold;

echo json_encode([
    'success'   => true,
    'state'     => $state,
    'rate'      => $rate,
    'is_free'   => $isFree,
    'formatted' => $isFree ? 'FREE' : '₦' . number_format($rate, 0),
]);
