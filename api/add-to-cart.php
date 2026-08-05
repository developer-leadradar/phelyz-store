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

// If product has colour variants, a colour must be selected.
// Skipped when the request carries a `variants` list, which is validated
// colour by colour further down.
$availableColors = parseProductColors($product['colors'] ?? '');
if (!empty($availableColors) && empty($data['variants'])) {
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

// A shopper buying the same piece in several colours sends them all together,
// so the whole selection lands in the cart from one tap rather than forcing
// them back to the swatches once per colour.
if (!empty($data['variants']) && is_array($data['variants'])) {
    $lines = [];
    $totalQty = 0;

    foreach ($data['variants'] as $v) {
        $vQty   = isset($v['quantity']) ? (int)$v['quantity'] : 0;
        $vColor = isset($v['color']) ? trim((string)$v['color']) : '';
        if ($vQty < 1 || $vColor === '') continue;

        $match = null;
        foreach ($availableColors as $c) {
            if (strcasecmp($c['name'], $vColor) === 0) { $match = $c['name']; break; }
        }
        if ($match === null) {
            echo json_encode(['success' => false, 'message' => 'One of those colours is not available.']);
            exit;
        }
        $lines[$match] = ($lines[$match] ?? 0) + $vQty;
        $totalQty     += $vQty;
    }

    if (!$lines) {
        echo json_encode(['success' => false, 'message' => 'Choose at least one colour and quantity.']);
        exit;
    }

    // Stock is held against the piece as a whole, not per colour, so the
    // combined quantity is what has to fit.
    if (!isPreorderProduct($product) && $product['stock_quantity'] < $totalQty) {
        echo json_encode([
            'success' => false,
            'message' => 'Only ' . $product['stock_quantity'] . ' available in total.'
        ]);
        exit;
    }

    $added = 0;
    foreach ($lines as $colour => $qty) {
        if (addToCart($productId, $qty, $colour)) $added++;
    }

    if (!$added) {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
        exit;
    }

    echo json_encode([
        'success'    => true,
        'message'    => $totalQty . ' item' . ($totalQty === 1 ? '' : 's') . ' added to cart',
        'cart_count' => getCartCount(),
    ]);
    exit;
}

// Stock check - pre-order items (express or sold-out) bypass the quantity limit
if (!isPreorderProduct($product) && $product['stock_quantity'] < $quantity) {
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