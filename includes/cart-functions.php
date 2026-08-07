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
        } catch (Exception $e) { /* table/column missing - keep defaults */ }
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
    } catch (Exception $e) { /* table/column missing - keep state-level result */ }

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
        // Pre-order items (express or sold-out) are always allowed through -
        // out-of-stock is now a valid pre-order, not a blocker.
        if (isPreorderProduct($item)) {
            continue;
        }
        // In-stock items: don't let the ordered quantity exceed availability.
        if ($item['stock_quantity'] < $item['quantity']) {
            $errors[] = $item['name'] . ' - only ' . $item['stock_quantity'] . ' in stock';
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

    // A coupon held in the session is re-checked on every request rather than
    // trusted, so one that expires or stops qualifying mid-visit drops off by
    // itself instead of quietly discounting the order at checkout.
    $discount     = 0.0;
    $couponCode   = '';
    $couponNotice = '';
    if (function_exists('couponSessionCode') && couponSessionCode() !== '') {
        $coupon = couponFind(couponSessionCode());
        $check  = couponValidate($coupon, $items, $subtotal, $shipping);
        if ($check['ok']) {
            $couponCode = strtoupper($coupon['code']);
            $discount   = (float)$check['discount'];
            if (!empty($check['free_shipping'])) $shipping = 0;
        } else {
            couponSessionClear();
            $couponNotice = $check['message'];
        }
    }

    $total = max(0, $subtotal - $discount) + $shipping;

    return [
        'items'          => $items,
        'item_count'     => $itemCount,
        'subtotal'       => $subtotal,
        'tax'            => $tax,
        'shipping'       => $shipping,
        'shipping_rate'  => $shippingRate,
        'shipping_state' => $selectedState,
        'threshold'      => $threshold,
        'discount'       => $discount,
        'coupon_code'    => $couponCode,
        'coupon_notice'  => $couponNotice,
        'total'          => $total,
    ];
}

require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/coupons.php';

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
        'user_id' => isLoggedIn() ? $_SESSION['user_id'] : null, // NULL = guest order
        'status' => 'pending',
        'subtotal' => $cartSummary['subtotal'],
        'tax' => $cartSummary['tax'],
        'shipping' => $cartSummary['shipping'],
        'discount' => $cartSummary['discount'] ?? 0,
        'coupon_code' => ($cartSummary['coupon_code'] ?? '') ?: null,
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

    // Attach first-touch marketing attribution so admin reports can credit
    // the channel that actually produced this sale.
    if (function_exists('analyticsAttribution')) {
        $attr = analyticsAttribution();
        $orderData['channel']      = $attr['channel']      ?? 'direct';
        $orderData['referrer']     = $attr['referrer']     ?? null;
        $orderData['utm_source']   = $attr['utm_source']   ?? null;
        $orderData['utm_campaign'] = $attr['utm_campaign'] ?? null;
    }
    
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

    // Track this order against the current session so a guest can view their
    // own confirmation without an account.
    if (!isset($_SESSION['guest_orders']) || !is_array($_SESSION['guest_orders'])) {
        $_SESSION['guest_orders'] = [];
    }
    $_SESSION['guest_orders'][] = (int)$orderResult['order_id'];

    // Cash-on-delivery and bank transfer are committed the moment the order is
    // placed. A card order is not: the customer is about to be handed over to
    // Paystack and may never pay. Until the money is confirmed we touch nothing
    // else - the cart stays full, the coupon stays applied, no stock moves, no
    // tracking number is issued and no confirmation email goes out. If they back
    // out of the payment page they come straight back to the cart they had.
    if (($formData['payment_method'] ?? 'cod') === 'paystack') {
        $_SESSION['phelyz_pending_order'] = (int)$orderResult['order_id'];
    } else {
        finaliseOrderAfterPayment((int)$orderResult['order_id']);
    }

    return [
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number']
    ];
}

/**
 * Everything that must happen exactly once, when an order is really committed.
 *
 * For cash-on-delivery and bank transfer that moment is when the order is
 * placed. For a card order it is when Paystack confirms the money - and that
 * confirmation can arrive twice, because the customer's browser comes back to
 * paystack-callback.php AND Paystack posts to paystack-webhook.php. Whichever
 * gets there first wins the atomic "flip to paid" update and calls this; the
 * steps below are written to survive a second call regardless.
 *
 * $clearTheCart is false for the webhook, which runs server-to-server on a
 * session that has nothing to do with the shopper.
 */
function finaliseOrderAfterPayment($orderId, $clearTheCart = true) {
    $db    = getDB();
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [(int)$orderId]);
    if (!$order) return false;

    // Stock. Guarded by its own stock_reduced flag.
    reduceStockForOrder((int)$orderId);

    // Coupon. One redemption row per order, so a repeat call must not double
    // count it against the code's usage limit.
    if (!empty($order['coupon_code'])) {
        $already = null;
        try {
            $already = $db->fetchOne(
                "SELECT id FROM coupon_redemptions WHERE order_id = ?", [(int)$orderId]
            );
        } catch (Exception $e) { /* table may not exist yet - non-fatal */ }
        if (!$already) {
            couponRecordRedemption(
                $order['coupon_code'], (int)$orderId, (float)($order['discount'] ?? 0)
            );
        }
    }

    // The tracking parcel. createParcelForOrder() would cheerfully add a second
    // one (-P2), so only create it when the order has none.
    if (function_exists('createParcelForOrder')) {
        $hasParcel = null;
        try {
            $hasParcel = $db->fetchOne("SELECT id FROM parcels WHERE order_id = ?", [(int)$orderId]);
        } catch (Exception $e) { /* parcels table may not exist yet - non-fatal */ }
        if (!$hasParcel) createParcelForOrder((int)$orderId);
    }

    if ($clearTheCart) {
        clearCart();
        couponSessionClear();
        unset($_SESSION['phelyz_pending_order']);
    }

    // Confirmation email. Look the address up from the order's own user rather
    // than the current session, because the webhook path has no shopper logged
    // in and would otherwise skip the email entirely.
    if (!empty($order['user_id'])) {
        $buyer = $db->fetchOne("SELECT email FROM users WHERE id = ?", [(int)$order['user_id']]);
        if (!empty($buyer['email'])) {
            sendOrderConfirmationEmail($buyer['email'], $order['order_number']);
        }
    }

    return true;
}

/**
 * Tidy up after a payment that completed while the shopper was not looking.
 *
 * If they paid on Paystack and then closed the tab instead of returning, the
 * webhook marked the order paid but their cart is still sitting there full of
 * things they have already bought. Called when the cart or checkout page loads.
 */
function cartSyncPendingOrder() {
    if (empty($_SESSION['phelyz_pending_order'])) return;

    $id  = (int)$_SESSION['phelyz_pending_order'];
    $row = getDB()->fetchOne("SELECT payment_status FROM orders WHERE id = ?", [$id]);

    if (!$row) { unset($_SESSION['phelyz_pending_order']); return; }

    if (($row['payment_status'] ?? '') === 'paid') {
        clearCart();
        couponSessionClear();
        unset($_SESSION['phelyz_pending_order']);
    }
}

function sendOrderConfirmationEmail($email, $orderNumber) {
    $subject = "Order Confirmation - " . $orderNumber;
    $message = phelyzEmailTemplate(
        '<p style="margin:0 0 12px;font-size:16px;">Thank you for your order.</p>'
      . '<p style="margin:0 0 18px;color:#44403C;">Your order <strong>' . htmlspecialchars($orderNumber) . '</strong> has been received and is being processed. We will email you again as soon as it ships.</p>'
      . phelyzEmailButton('Track My Order', SITE_URL . '/track.php')
      . '<p style="margin:0;color:#78716C;font-size:13px;">Questions about this order? Just reply to this email and our team will help.</p>',
        'Order ' . $orderNumber . ' received and being processed.'
    );
    
    emailContext(['category'=>'transactional','source_type'=>'order_confirmation','source_id'=>$orderNumber]);
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