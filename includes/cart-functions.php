<?php
// Additional Cart-Specific Functions

/**
 * Resolve which payment methods are available for the current cart in a given state.
 * Logic:
 *   - Per-state toggle (shipping_rates.cod_enabled / bank_enabled) is the baseline.
 *   - Each cart product can override (products.cod_enabled / bank_enabled). NULL = inherit.
 *   - A method is offered only when state-allows AND every item allows it.
 *   - Falls back to "both enabled" if tables/columns are missing.
 *
 * Returns: ['cod' => bool, 'bank' => bool]
 */
function getAvailablePaymentMethods($state = null) {
    $methods = ['cod' => true, 'bank' => true];
    $db = getDB();

    // State-level
    if ($state) {
        try {
            $row = $db->fetchOne(
                "SELECT cod_enabled, bank_enabled FROM shipping_rates WHERE state = ? LIMIT 1",
                [$state]
            );
            if ($row) {
                $methods['cod']  = (int)$row['cod_enabled']  === 1;
                $methods['bank'] = (int)$row['bank_enabled'] === 1;
            }
        } catch (Exception $e) { /* table/column missing — keep defaults */ }
    }

    // Product-level intersection
    try {
        $cart  = getOrCreateCart();
        $items = $db->fetchAll(
            "SELECT p.cod_enabled, p.bank_enabled
             FROM cart_items ci JOIN products p ON ci.product_id = p.id
             WHERE ci.cart_id = ?",
            [$cart['id']]
        );
        foreach ($items as $it) {
            if ($it['cod_enabled']  !== null && (int)$it['cod_enabled']  !== 1) $methods['cod']  = false;
            if ($it['bank_enabled'] !== null && (int)$it['bank_enabled'] !== 1) $methods['bank'] = false;
        }
    } catch (Exception $e) { /* table/column missing — keep state-level result */ }

    return $methods;
}

function getShippingRateByState($state) {
    if (empty($state)) return getDefaultShippingRate();
    try {
        $db  = getDB();
        $row = $db->fetchOne("SELECT rate FROM shipping_rates WHERE state = ?", [$state]);
        if ($row) return (float)$row['rate'];
    } catch (Exception $e) {}
    return getDefaultShippingRate();
}

function getDefaultShippingRate() {
    $settingsFile = __DIR__ . '/../data/settings.json';
    if (file_exists($settingsFile)) {
        $s = json_decode(file_get_contents($settingsFile), true);
        if (isset($s['shipping_fee'])) return (float)$s['shipping_fee'];
    }
    return 2500.00;
}

function getFreeShippingThreshold() {
    $settingsFile = __DIR__ . '/../data/settings.json';
    if (file_exists($settingsFile)) {
        $s = json_decode(file_get_contents($settingsFile), true);
        if (isset($s['free_shipping_threshold'])) return (float)$s['free_shipping_threshold'];
    }
    return 50000.00;
}

function validateCartStock() {
    $items = getCartItems();
    $errors = [];

    foreach ($items as $item) {
        $status = $item['stock_status'] ?? 'available';
        if ($status === 'out_of_stock') {
            $errors[] = $item['name'] . ' is currently out of stock';
        } elseif ($status !== 'express' && $item['stock_quantity'] < $item['quantity']) {
            $errors[] = $item['name'] . ' — only ' . $item['stock_quantity'] . ' in stock';
        }
    }

    return $errors;
}

function getCartSummary($selectedState = null) {
    $items     = getCartItems();
    $subtotal  = 0;
    $itemCount = 0;

    foreach ($items as $item) {
        $subtotal  += $item['price'] * $item['quantity'];
        $itemCount += $item['quantity'];
    }

    // Determine shipping state (passed in > session > null)
    if ($selectedState === null) {
        $selectedState = $_SESSION['phelyz_shipping_state'] ?? null;
    } else {
        $_SESSION['phelyz_shipping_state'] = $selectedState;
    }

    $threshold    = getFreeShippingThreshold();
    $shippingRate = $selectedState ? getShippingRateByState($selectedState) : getDefaultShippingRate();
    $shipping     = $subtotal >= $threshold ? 0 : $shippingRate;

    $tax   = 0;
    $total = $subtotal + $shipping;

    return [
        'items'          => $items,
        'item_count'     => $itemCount,
        'subtotal'       => $subtotal,
        'tax'            => $tax,
        'shipping'       => $shipping,
        'shipping_rate'  => $shippingRate,
        'shipping_state' => $selectedState,
        'threshold'      => $threshold,
        'total'          => $total,
    ];
}

function processCheckout($formData) {
    $db = getDB();
    $cartSummary = getCartSummary($formData['shipping_state'] ?? null);
    
    // Validate stock before processing
    $stockErrors = validateCartStock();
    if (!empty($stockErrors)) {
        return [
            'success' => false,
            'message' => 'Stock validation failed',
            'errors' => $stockErrors
        ];
    }
    
    // Prepare order data
    $orderData = [
        'user_id' => isLoggedIn() ? $_SESSION['user_id'] : 0,
        'status' => 'pending',
        'subtotal' => $cartSummary['subtotal'],
        'tax' => $cartSummary['tax'],
        'shipping' => $cartSummary['shipping'],
        'total' => $cartSummary['total'],
        'payment_method' => $formData['payment_method'] ?? 'cod',
        'shipping_first_name' => $formData['shipping_first_name'],
        'shipping_last_name' => $formData['shipping_last_name'],
        'shipping_address' => $formData['shipping_address'],
        'shipping_city' => $formData['shipping_city'],
        'shipping_state' => $formData['shipping_state'],
        'shipping_zip' => $formData['shipping_zip'],
        'shipping_country' => $formData['shipping_country'] ?? 'Nigeria',
        'shipping_phone' => $formData['shipping_phone'],
        'billing_first_name' => $formData['billing_first_name'] ?? $formData['shipping_first_name'],
        'billing_last_name' => $formData['billing_last_name'] ?? $formData['shipping_last_name'],
        'billing_address' => $formData['billing_address'] ?? $formData['shipping_address'],
        'billing_city' => $formData['billing_city'] ?? $formData['shipping_city'],
        'billing_state' => $formData['billing_state'] ?? $formData['shipping_state'],
        'billing_zip' => $formData['billing_zip'] ?? $formData['shipping_zip'],
        'billing_country' => $formData['billing_country'] ?? $formData['shipping_country'] ?? 'Nigeria',
        'billing_phone' => $formData['billing_phone'] ?? $formData['shipping_phone'],
        'notes' => $formData['notes'] ?? ''
    ];
    
    // Create order
    $orderResult = createOrder($orderData);
    
    if (!$orderResult['success']) {
        return [
            'success' => false,
            'message' => 'Failed to create order'
        ];
    }
    
    // Add order items
    addOrderItems($orderResult['order_id'], $cartSummary['items']);
    
    // Clear cart
    clearCart();
    
    // Send confirmation email
    if (isLoggedIn()) {
        $user = getCurrentUser();
        sendOrderConfirmationEmail($user['email'], $orderResult['order_number']);
    }
    
    return [
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number']
    ];
}

function sendOrderConfirmationEmail($email, $orderNumber) {
    $subject = "Order Confirmation - " . $orderNumber;
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Thank you for your order!</h2>
        <p>Your order <strong>$orderNumber</strong> has been received and is being processed.</p>
        <p>We'll send you another email when your order has been shipped.</p>
        <p>Best regards,<br>Phelyz Diamond Store</p>
    </body>
    </html>
    ";
    
    sendEmail($email, $subject, $message);
}

function mergeGuestCart($userId) {
    // Merge guest cart with user cart after login
    $db = getDB();
    $sessionId = session_id();
    
    $guestCart = $db->fetchOne(
        "SELECT * FROM cart WHERE session_id = ?",
        [$sessionId]
    );
    
    if (!$guestCart) {
        return;
    }
    
    $userCart = $db->fetchOne(
        "SELECT * FROM cart WHERE user_id = ?",
        [$userId]
    );
    
    if (!$userCart) {
        // Update guest cart to user cart
        $db->update(
            'cart',
            ['user_id' => $userId, 'session_id' => null],
            'id = ?',
            [$guestCart['id']]
        );
    } else {
        // Merge items from guest cart to user cart
        $guestItems = $db->fetchAll(
            "SELECT * FROM cart_items WHERE cart_id = ?",
            [$guestCart['id']]
        );
        
        foreach ($guestItems as $item) {
            $existingItem = $db->fetchOne(
                "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?",
                [$userCart['id'], $item['product_id']]
            );
            
            if ($existingItem) {
                $db->update(
                    'cart_items',
                    ['quantity' => $existingItem['quantity'] + $item['quantity']],
                    'id = ?',
                    [$existingItem['id']]
                );
            } else {
                $db->insert('cart_items', [
                    'cart_id' => $userCart['id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        }
        
        // Delete guest cart
        $db->delete('cart_items', 'cart_id = ?', [$guestCart['id']]);
        $db->delete('cart', 'id = ?', [$guestCart['id']]);
    }
}
?>