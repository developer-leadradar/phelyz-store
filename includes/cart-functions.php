<?php
// Additional Cart-Specific Functions

function validateCartStock() {
    $items = getCartItems();
    $errors = [];
    
    foreach ($items as $item) {
        if ($item['stock_quantity'] < $item['quantity']) {
            $errors[] = $item['name'] . ' - Only ' . $item['stock_quantity'] . ' in stock';
        }
    }
    
    return $errors;
}

function getCartSummary() {
    $items = getCartItems();
    $subtotal = 0;
    $itemCount = 0;
    
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $itemCount += $item['quantity'];
    }
    
    $tax = $subtotal * 0.05; // 5% tax
    $shipping = $subtotal >= 50000 ? 0 : 2500; // Free shipping over ₦50,000
    $total = $subtotal + $tax + $shipping;
    
    return [
        'items' => $items,
        'item_count' => $itemCount,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total
    ];
}

function processCheckout($formData) {
    $db = getDB();
    $cartSummary = getCartSummary();
    
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