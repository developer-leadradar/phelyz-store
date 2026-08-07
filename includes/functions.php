<?php
// Phelyz Diamond Store - Helper Functions
// ===========================================

// Database Helper
function getDB() {
    return Database::getInstance();
}

// ===========================================
// AUTHENTICATION FUNCTIONS
// ===========================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (isAdmin()) {
        redirect('admin/index.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        // Absolute, not relative. Called from a page already inside /admin/,
        // a relative path resolved to /admin/admin/login.php.
        redirect(SITE_URL . '/admin/login.php');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    return $db->fetchOne(
        "SELECT * FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

function login($email, $password) {
    $db = getDB();
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE email = ? AND is_active = 1",
        [$email]
    );
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    redirect('index.php');
}

function register($data) {
    $db = getDB();
    
    // Check if email exists
    $exists = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
    if ($exists) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $data['password'] = password_hash($data['password'], PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
    
    $userId = $db->insert('users', $data);
    
    if ($userId) {
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

// ===========================================
// CATEGORY FUNCTIONS
// ===========================================

function getAllCategories($activeOnly = true) {
    $db = getDB();
    $sql = "SELECT * FROM categories WHERE parent_id IS NULL";
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $sql .= " ORDER BY display_order ASC";
    return $db->fetchAll($sql);
}

function getCategoryById($id) {
    $db = getDB();
    return $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
}

function getCategoryBySlug($slug) {
    $db = getDB();
    return $db->fetchOne("SELECT * FROM categories WHERE slug = ?", [$slug]);
}

function getSubcategories($parentId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY display_order ASC",
        [$parentId]
    );
}

// ===========================================
// PRODUCT FUNCTIONS
// ===========================================

function getAllProducts($filters = [], $limit = null, $offset = 0) {
    $db = getDB();
    
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1";
    $params = [];
    
    // Apply filters
    if (!empty($filters['category_id'])) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filters['category_id'];
    }
    
    if (!empty($filters['search'])) {
        $stems = searchStems($filters['search']);
        $likeClauses = [];
        foreach ($stems as $stem) {
            $likeClauses[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
            $t = '%' . $stem . '%';
            $params[] = $t; $params[] = $t; $params[] = $t;
        }
        $sql .= " AND (" . implode(' OR ', $likeClauses) . ")";
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND p.price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND p.price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['material'])) {
        $sql .= " AND p.material = ?";
        $params[] = $filters['material'];
    }
    
    if (!empty($filters['metal_purity'])) {
        $sql .= " AND p.metal_purity = ?";
        $params[] = $filters['metal_purity'];
    }
    
    if (!empty($filters['stone_type'])) {
        $sql .= " AND p.stone_type = ?";
        $params[] = $filters['stone_type'];
    }
    
    if (!empty($filters['brand'])) {
        $sql .= " AND p.brand = ?";
        $params[] = $filters['brand'];
    }
    
    if (!empty($filters['gender'])) {
        $sql .= " AND p.gender = ?";
        $params[] = $filters['gender'];
    }
    
    if (!empty($filters['style'])) {
        $sql .= " AND p.style = ?";
        $params[] = $filters['style'];
    }
    
    if (!empty($filters['occasion'])) {
        $sql .= " AND p.occasion = ?";
        $params[] = $filters['occasion'];
    }
    
    if (!empty($filters['in_stock'])) {
        $sql .= " AND p.stock_quantity > 0";
    }
    
    if (!empty($filters['featured'])) {
        $sql .= " AND p.is_featured = 1";
    }
    
    if (!empty($filters['min_rating'])) {
        $sql .= " AND p.rating >= ?";
        $params[] = $filters['min_rating'];
    }
    
    // Sorting
    $orderBy = "p.created_at DESC";
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
            case 'price_low':
                $orderBy = "p.price ASC";
                break;
            case 'price_desc':
            case 'price_high':
                $orderBy = "p.price DESC";
                break;
            case 'rating':
            case 'popular':
                $orderBy = "p.rating DESC, p.review_count DESC";
                break;
            case 'name_asc':
                $orderBy = "p.name ASC";
                break;
            case 'name_desc':
                $orderBy = "p.name DESC";
                break;
            case 'newest':
            default:
                $orderBy = "p.created_at DESC";
                break;
        }
    }
    
    $sql .= " ORDER BY $orderBy";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
    }
    
    return $db->fetchAll($sql, $params);
}

function getProductById($id) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT p.*, c.name as category_name FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = ?",
        [$id]
    );
}

function getProductBySlug($slug) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT p.*, c.name as category_name FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.slug = ?",
        [$slug]
    );
}

function getFeaturedProducts($limit = 8) {
    return getAllProducts(['featured' => true], $limit);
}

function getRelatedProducts($productId, $categoryId, $limit = 4) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT * FROM products
         WHERE category_id = ? AND id != ? AND is_active = 1
         ORDER BY RANDOM()
         LIMIT ?",
        [$categoryId, $productId, $limit]
    );
}

function countProducts($filters = []) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $params = [];
    
    // Apply same filters as getAllProducts
    if (!empty($filters['category_id'])) {
        $sql .= " AND category_id = ?";
        $params[] = $filters['category_id'];
    }
    
    if (!empty($filters['search'])) {
        $stems = searchStems($filters['search']);
        $likeClauses = [];
        foreach ($stems as $stem) {
            $likeClauses[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $t = '%' . $stem . '%';
            $params[] = $t; $params[] = $t; $params[] = $t;
        }
        $sql .= " AND (" . implode(' OR ', $likeClauses) . ")";
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['material'])) {
        $sql .= " AND material = ?";
        $params[] = $filters['material'];
    }
    
    if (!empty($filters['stone_type'])) {
        $sql .= " AND stone_type = ?";
        $params[] = $filters['stone_type'];
    }
    
    if (!empty($filters['brand'])) {
        $sql .= " AND brand = ?";
        $params[] = $filters['brand'];
    }
    
    if (!empty($filters['gender'])) {
        $sql .= " AND gender = ?";
        $params[] = $filters['gender'];
    }
    
    if (!empty($filters['in_stock'])) {
        $sql .= " AND stock_quantity > 0";
    }
    
    $result = $db->fetchOne($sql, $params);
    return $result ? $result['total'] : 0;
}

function getFilterOptions($field) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT DISTINCT $field FROM products WHERE $field IS NOT NULL AND $field != '' AND is_active = 1 ORDER BY $field"
    );
}

// ===========================================
// CART FUNCTIONS
// ===========================================

function getOrCreateCart() {
    $db = getDB();
    
    if (isLoggedIn()) {
        $cart = $db->fetchOne(
            "SELECT * FROM cart WHERE user_id = ?",
            [$_SESSION['user_id']]
        );
        
        if (!$cart) {
            $cartId = $db->insert('cart', ['user_id' => $_SESSION['user_id']]);
            $cart = ['id' => $cartId];
        }
    } else {
        $sessionId = session_id();
        $cart = $db->fetchOne(
            "SELECT * FROM cart WHERE session_id = ?",
            [$sessionId]
        );
        
        if (!$cart) {
            $cartId = $db->insert('cart', ['session_id' => $sessionId]);
            $cart = ['id' => $cartId];
        }
    }
    
    return $cart;
}

/**
 * Effective stock state of a product, reconciling the manual stock_status
 * flag with the live stock_quantity.
 *
 * Returns one of:
 *   'in_stock'  - available and quantity > 0 (buy now, decrements stock)
 *   'express'   - admin-marked pre-order
 *   'preorder'  - out of stock (manual flag OR quantity hit 0) → buyable as pre-order
 */
function effectiveStockStatus($product) {
    $status = $product['stock_status'] ?? 'available';
    $qty    = (int)($product['stock_quantity'] ?? 0);
    if ($status === 'express')       return 'express';
    if ($status === 'out_of_stock')  return 'preorder';
    // available:
    return $qty > 0 ? 'in_stock' : 'preorder';
}

/** Is this product a pre-order (express or sold-out)? Pre-orders bypass stock limits. */
function isPreorderProduct($product) {
    $eff = effectiveStockStatus($product);
    return $eff === 'express' || $eff === 'preorder';
}

function addToCart($productId, $quantity = 1, $selectedColor = null) {
    $db = getDB();
    $cart = getOrCreateCart();

    // Check if product exists
    $product = getProductById($productId);
    if (!$product) {
        return false;
    }

    // Pre-order items (express or sold-out) bypass stock limits.
    // Out-of-stock is now purchasable as a pre-order rather than blocked.
    $isPreorder = isPreorderProduct($product);

    // For in-stock items, don't let the cart exceed available quantity
    if (!$isPreorder && $product['stock_quantity'] < $quantity) {
        return false;
    }

    // Validate color against product's available colors (if any)
    $selectedColor = $selectedColor !== null ? trim($selectedColor) : null;
    if ($selectedColor === '') $selectedColor = null;

    // Check if item already in cart with the same color (null-safe equality, portable to MySQL + PG)
    $existingItem = $db->fetchOne(
        "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?
         AND (selected_color = ? OR (selected_color IS NULL AND ? IS NULL))",
        [$cart['id'], $productId, $selectedColor, $selectedColor]
    );

    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        if (!$isPreorder && $newQuantity > $product['stock_quantity']) {
            return false;
        }

        return $db->update(
            'cart_items',
            ['quantity' => $newQuantity],
            'id = ?',
            [$existingItem['id']]
        );
    } else {
        // Add new item
        return $db->insert('cart_items', [
            'cart_id' => $cart['id'],
            'product_id' => $productId,
            'quantity' => $quantity,
            'selected_color' => $selectedColor
        ]);
    }
}

function getCartItems() {
    $db = getDB();
    $cart = getOrCreateCart();

    return $db->fetchAll(
        "SELECT ci.*, p.name, p.price, p.image, p.stock_quantity, p.stock_status, p.colors
         FROM cart_items ci
         JOIN products p ON ci.product_id = p.id
         WHERE ci.cart_id = ?",
        [$cart['id']]
    );
}

/**
 * Parse a product's colors field into an array of ['name' => ..., 'hex' => ...].
 * Format stored: "Gold|#CA8A04,Rose Gold|#E8B4A0,Silver|#C0C0C0"
 * Falls back to no hex if separator missing: "Gold,Silver"
 */
function parseProductColors($colorsStr) {
    $out = [];
    if (!$colorsStr) return $out;
    foreach (explode(',', $colorsStr) as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') continue;
        if (strpos($chunk, '|') !== false) {
            [$name, $hex] = array_map('trim', explode('|', $chunk, 2));
        } else {
            $name = $chunk;
            $hex  = '';
        }
        if ($name === '') continue;
        // Validate hex (#rgb or #rrggbb), else blank
        $hex = preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $hex) ? $hex : '';
        $out[] = ['name' => $name, 'hex' => $hex];
    }
    return $out;
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

function getCartCount() {
    $items = getCartItems();
    $count = 0;
    
    foreach ($items as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

function updateCartQuantity($cartItemId, $quantity) {
    $db = getDB();
    
    if ($quantity <= 0) {
        return removeFromCart($cartItemId);
    }
    
    return $db->update(
        'cart_items',
        ['quantity' => $quantity],
        'id = ?',
        [$cartItemId]
    );
}

function removeFromCart($cartItemId) {
    $db = getDB();
    return $db->delete('cart_items', 'id = ?', [$cartItemId]);
}

function clearCart() {
    $db = getDB();
    $cart = getOrCreateCart();
    return $db->delete('cart_items', 'cart_id = ?', [$cart['id']]);
}

// ===========================================
// ORDER FUNCTIONS
// ===========================================

/**
 * Next sequential order number, e.g. PHZ-2026-000417.
 * Uses an atomic counter row per year so two simultaneous checkouts can never
 * receive the same number (the old rand() generator collided at ~1-in-9999).
 */
function generateOrderNumber() {
    $db   = getDB();
    $year = (int)date('Y');
    $seq  = 0;

    try {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
        if ($driver === 'pgsql') {
            $row = $db->fetchOne(
                "INSERT INTO order_counters (year_key, last_seq) VALUES (?, 1)
                 ON CONFLICT (year_key) DO UPDATE SET last_seq = order_counters.last_seq + 1
                 RETURNING last_seq",
                [$year]
            );
            $seq = (int)($row['last_seq'] ?? 0);
        } else {
            $db->query(
                "INSERT INTO order_counters (year_key, last_seq) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE last_seq = last_seq + 1",
                [$year]
            );
            $row = $db->fetchOne("SELECT last_seq FROM order_counters WHERE year_key = ?", [$year]);
            $seq = (int)($row['last_seq'] ?? 0);
        }
    } catch (Exception $e) {
        $seq = 0; // counter table missing - fall through
    }

    if ($seq <= 0) {
        // Fallback: derive from the current order count so we stay monotonic
        try {
            $c = $db->fetchOne("SELECT COUNT(*) AS c FROM orders");
            $seq = (int)($c['c'] ?? 0) + 1;
        } catch (Exception $e) {
            $seq = (int)(microtime(true) * 100) % 1000000;
        }
    }

    return sprintf('PHZ-%d-%06d', $year, $seq);
}

function createOrder($orderData) {
    $db = getDB();

    $orderNumber = generateOrderNumber();
    $orderData['order_number'] = $orderNumber;

    $orderId = $db->insert('orders', $orderData);
    
    if ($orderId) {
        return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
    }
    
    return ['success' => false];
}

function addOrderItems($orderId, $items) {
    $db = getDB();

    foreach ($items as $item) {
        $db->insert('order_items', [
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_name' => $item['name'],
            'quantity' => $item['quantity'],
            'selected_color' => $item['selected_color'] ?? null,
            'price_at_purchase' => $item['price'],
            'subtotal' => $item['price'] * $item['quantity']
        ]);
    }
    // NOTE: stock is reduced separately via reduceStockForOrder() - only once
    // the order is actually committed (COD/bank at placement, card on payment).
    return true;
}

/**
 * Reduce stock for a committed order. Idempotent: uses orders.stock_reduced
 * as a guard so callback + webhook can't double-decrement.
 * - In-stock ('available') items decrement, floored at 0.
 * - Pre-order items (express / out_of_stock) are NOT decremented.
 */
function reduceStockForOrder($orderId) {
    $db = getDB();

    // Guard against double reduction
    try {
        $order = $db->fetchOne("SELECT id, stock_reduced FROM orders WHERE id = ?", [$orderId]);
        if (!$order) return false;
        if (!empty($order['stock_reduced'])) return true; // already done
    } catch (Exception $e) {
        // stock_reduced column may not exist yet - fall through and still reduce once
    }

    $items = $db->fetchAll(
        "SELECT oi.product_id, oi.quantity, p.stock_status
         FROM order_items oi JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?",
        [$orderId]
    );

    foreach ($items as $it) {
        $status = $it['stock_status'] ?? 'available';
        // Only decrement real in-stock items; pre-orders keep their (0/neg) count
        if ($status === 'available') {
            $db->query(
                "UPDATE products
                 SET stock_quantity = CASE WHEN stock_quantity - ? < 0 THEN 0 ELSE stock_quantity - ? END
                 WHERE id = ?",
                [$it['quantity'], $it['quantity'], $it['product_id']]
            );
        }
    }

    try {
        $db->update('orders', ['stock_reduced' => 1], 'id = ?', [$orderId]);
    } catch (Exception $e) { /* column missing - non-fatal */ }

    return true;
}

function getOrdersByUser($userId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
        [$userId]
    );
}

function getOrderById($orderId) {
    $db = getDB();
    return $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
}

function getOrderItems($orderId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT oi.*, p.image FROM order_items oi 
         LEFT JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = ?",
        [$orderId]
    );
}

function updateOrderStatus($orderId, $status) {
    $db = getDB();
    return $db->update('orders', ['status' => $status], 'id = ?', [$orderId]);
}

// ===========================================
// WISHLIST FUNCTIONS
// ===========================================

function addToWishlist($productId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    
    // Check if already in wishlist
    $exists = $db->fetchOne(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$_SESSION['user_id'], $productId]
    );
    
    if ($exists) {
        return true;
    }
    
    return $db->insert('wishlist', [
        'user_id' => $_SESSION['user_id'],
        'product_id' => $productId
    ]);
}

function removeFromWishlist($productId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    return $db->delete(
        'wishlist',
        'user_id = ? AND product_id = ?',
        [$_SESSION['user_id'], $productId]
    );
}

function getWishlistItems() {
    if (!isLoggedIn()) {
        return [];
    }
    
    $db = getDB();
    return $db->fetchAll(
        "SELECT w.*, p.* FROM wishlist w 
         JOIN products p ON w.product_id = p.id 
         WHERE w.user_id = ? 
         ORDER BY w.created_at DESC",
        [$_SESSION['user_id']]
    );
}

function isInWishlist($productId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $item = $db->fetchOne(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$_SESSION['user_id'], $productId]
    );
    
    return $item !== null;
}

// ===========================================
// UTILITY FUNCTIONS
// ===========================================

function redirect($url) {
    // Discard any buffered output so the Location header can be sent
    while (ob_get_level() > 0) { @ob_end_clean(); }

    if (!headers_sent()) {
        header("Location: $url");
    } else {
        // Headers already flushed (no output buffering, e.g. Vercel):
        // fall back to a client-side redirect instead of dying blank.
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
    }
    exit;
}

function searchStems($query) {
    $stems = [$query];
    $q = strtolower($query);
    if (substr($q, -3) === 'ies') {
        $stems[] = substr($query, 0, -3) . 'y';
    } elseif (substr($q, -2) === 'es') {
        $stems[] = substr($query, 0, -2);
        $stems[] = substr($query, 0, -1);
    } elseif (substr($q, -1) === 's') {
        $stems[] = substr($query, 0, -1);
    } else {
        $stems[] = $query . 's';
        $stems[] = $query . 'es';
    }
    return array_unique($stems);
}

/**
 * Every word worth matching a search against: product names, categories,
 * materials, stones and brands. Cached for the life of the request.
 */
function searchVocabulary() {
    static $vocab = null;
    if ($vocab !== null) return $vocab;

    $vocab = [];
    try {
        $rows = getDB()->fetchAll(
            "SELECT p.name, p.material, p.stone_type, p.brand, p.style, p.occasion, c.name AS category
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1"
        );
        foreach ($rows as $r) {
            foreach ($r as $value) {
                if (!$value) continue;
                foreach (preg_split('/[^a-zA-Z]+/', (string)$value) as $word) {
                    $word = strtolower($word);
                    if (strlen($word) >= 3) $vocab[$word] = true;
                }
            }
        }
    } catch (Exception $e) {
        // A search should still work if this lookup fails.
    }
    $vocab = array_keys($vocab);
    return $vocab;
}

/**
 * Nudge a misspelled search onto the nearest real word from the catalogue,
 * so "neclace" or "braclet" still find something.
 *
 * @return array [correctedQuery, bool didChange]
 */
function searchCorrectQuery($query) {
    $vocab = searchVocabulary();
    if (!$vocab) return [$query, false];

    $tokens  = preg_split('/(\s+)/', trim($query), -1, PREG_SPLIT_DELIM_CAPTURE);
    $changed = false;

    foreach ($tokens as $i => $token) {
        $word = strtolower($token);
        if (strlen($word) < 3 || !ctype_alpha($word)) continue;

        // Already part of a real word ("neck" inside "necklace")? Leave it alone.
        // Only this direction counts: a typo like "earing" happens to contain
        // "ring", and treating that as a hit would skip the correction.
        foreach ($vocab as $v) {
            if (strpos($v, $word) !== false) continue 2;
        }

        // How far off we tolerate: roughly a third of the word.
        $limit = strlen($word) <= 5 ? 1 : (strlen($word) <= 8 ? 2 : 3);
        $best = null; $bestScore = null;
        $wordSound = metaphone($word);

        foreach ($vocab as $v) {
            if (abs(strlen($v) - strlen($word)) > $limit + 1) continue;
            $d = levenshtein($word, $v);
            // Words that sound the same get treated as a near-miss.
            if ($d > $limit && $wordSound !== '' && metaphone($v) === $wordSound) $d = $limit;
            if ($d > $limit) continue;

            // Two candidates can sit the same distance away ("earing" is two
            // edits from both "ring" and "earrings"). Break the tie on the
            // longest shared opening, which is nearly always what was meant.
            $prefix = 0;
            $max = min(strlen($word), strlen($v));
            while ($prefix < $max && $word[$prefix] === $v[$prefix]) $prefix++;

            $score = [$d, -$prefix, abs(strlen($v) - strlen($word))];
            if ($bestScore === null || $score < $bestScore) { $bestScore = $score; $best = $v; }
        }

        if ($best !== null) { $tokens[$i] = $best; $changed = true; }
    }

    return [implode('', $tokens), $changed];
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Clean text that is going into the database and will be HTML-escaped again
 * when it is displayed.
 *
 * sanitize() HTML-encodes, which is right for values echoed straight out but
 * wrong for stored content: "Men's watch" was saved as "Men&#039;s watch" and
 * then escaped a second time on the way out, so the customer saw the raw
 * entity. Strip tags, leave the characters alone, and escape at output.
 */
function cleanText($data) {
    return trim(strip_tags((string)$data));
}

/**
 * Build the next free SKU for a category, e.g. RING-004.
 *
 * Takes the highest number already issued for that prefix and adds one, so
 * deleting a product does not cause the next one to reuse its code.
 */
function generateSku($categoryName, $productName = '') {
    $source = trim((string)$categoryName) !== '' ? $categoryName : $productName;
    $base   = strtoupper(preg_replace('/[^A-Za-z]/', '', $source));
    $base   = $base !== '' ? substr($base, 0, 4) : 'PHZ';

    $next = 1;
    try {
        $db  = getDB();
        $row = $db->fetchOne(
            "SELECT sku FROM products
             WHERE sku REGEXP ?
             ORDER BY CAST(SUBSTRING(sku, ?) AS UNSIGNED) DESC
             LIMIT 1",
            ['^' . $base . '-[0-9]+$', strlen($base) + 2]
        );
        if ($row && preg_match('/-(\d+)$/', $row['sku'], $m)) {
            $next = (int)$m[1] + 1;
        }

        // Belt and braces: skip anything already taken.
        for ($i = 0; $i < 500; $i++) {
            $candidate = $base . '-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
            if (!$db->fetchOne("SELECT id FROM products WHERE sku = ?", [$candidate])) {
                return $candidate;
            }
            $next++;
        }
    } catch (Exception $e) {
        // Fall through to a timestamped code rather than blocking the save.
    }
    return $base . '-' . date('ymdHis');
}

/**
 * Suggestions for a free-text product field: the standard options plus
 * anything the admin has typed before, so custom entries become reusable.
 */
function productFieldSuggestions($column, array $defaults = []) {
    $allowed = ['material', 'metal_purity', 'stone_type', 'brand', 'style', 'occasion'];
    if (!in_array($column, $allowed, true)) return $defaults;

    $values = $defaults;
    try {
        $rows = getDB()->fetchAll(
            "SELECT DISTINCT $column AS v FROM products
             WHERE $column IS NOT NULL AND $column <> '' ORDER BY $column ASC"
        );
        foreach ($rows as $r) {
            if (!in_array($r['v'], $values, true)) $values[] = $r['v'];
        }
    } catch (Exception $e) {
        // Defaults alone are fine.
    }
    return $values;
}

/**
 * Absolute URL for a stored product image.
 *
 * Image paths are saved relative ("uploads/products/x.jpg"). That resolves
 * correctly from the storefront but not from /admin/, where the browser looks
 * for /admin/uploads/... and gets nothing. Always hand out a full URL.
 */
function productImageUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^(https?:)?//#i', $path) || stripos($path, 'data:') === 0) return $path;
    return SITE_URL . '/' . ltrim($path, '/');
}

function formatPrice($price) {
    return '₦' . number_format($price, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Get all gallery images for a product (ordered).
 * Returns array of rows with keys: id, product_id, image_path, sort_order, is_primary.
 * Falls back to an empty array if the product_images table doesn't exist yet.
 */
function getProductImages($productId) {
    $db = getDB();
    try {
        return $db->fetchAll(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC",
            [$productId]
        );
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get the full gallery for a product, combining the legacy products.image
 * (as the first/primary entry) with all rows from product_images.
 * Returns an array of image URLs (strings).
 */
function getProductGallery($product) {
    $gallery = [];
    if (!empty($product['image'])) {
        $gallery[] = $product['image'];
    }
    foreach (getProductImages($product['id']) as $img) {
        if (!empty($img['image_path']) && !in_array($img['image_path'], $gallery, true)) {
            $gallery[] = $img['image_path'];
        }
    }
    return $gallery;
}

/**
 * Turn a PHP upload error code into something a shop owner can act on.
 *
 * Returns '' when the file arrived cleanly. Worth showing rather than
 * swallowing: a photo that is one megabyte over the limit otherwise vanishes
 * without a word and the product saves with a placeholder picture.
 */
function uploadErrorMessage($code, $filename = '') {
    $name = $filename !== '' ? '"' . $filename . '"' : 'That file';
    switch ((int)$code) {
        case UPLOAD_ERR_OK:
            return '';
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $name . ' is bigger than this server accepts (limit is '
                 . ini_get('upload_max_filesize') . ' per photo).';
        case UPLOAD_ERR_PARTIAL:
            return $name . ' only uploaded part way. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return '';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return $name . ' could not be saved on the server. Please contact support.';
        case UPLOAD_ERR_EXTENSION:
            return $name . ' was blocked by the server.';
        default:
            return $name . ' could not be uploaded.';
    }
}

function uploadImage($file, $directory = 'products') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;

    $newFilename = $directory . '/' . uniqid() . '.' . $ext;

    // Use Supabase Storage in production
    if (!empty(SUPABASE_URL) && !empty(SUPABASE_SERVICE_KEY)) {
        $fileContent = file_get_contents($file['tmp_name']);
        $mimeTypes   = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
        $mime        = $mimeTypes[$ext] ?? 'application/octet-stream';
        $url         = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $newFilename;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
                'apikey: ' . SUPABASE_SERVICE_KEY,
                'Content-Type: ' . $mime,
                'x-upsert: true',
            ],
            CURLOPT_POSTFIELDS     => $fileContent,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 || $code === 201) {
            return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $newFilename;
        }
        error_log('Supabase Storage upload failed: ' . $response);
        return false;
    }

    // Local fallback
    $uploadDir = UPLOAD_PATH . $directory . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $uploadDir . basename($newFilename))) {
        // Phone cameras produce 3-5MB files. Served untouched they crawl in over
        // mobile data and paint down the screen in bands, so shrink to something
        // sensible for the largest slot the design ever uses.
        optimiseImageFile($uploadDir . basename($newFilename));
        return 'uploads/' . $newFilename;
    }
    return false;
}

/** memory_limit in bytes. Returns 0 when the limit is unlimited. */
function imageMemoryLimitBytes() {
    $raw = trim((string)ini_get('memory_limit'));
    if ($raw === '' || $raw === '-1') return 0;
    $unit = strtolower(substr($raw, -1));
    $n    = (float)$raw;
    if ($unit === 'g') return (int)($n * 1024 * 1024 * 1024);
    if ($unit === 'm') return (int)($n * 1024 * 1024);
    if ($unit === 'k') return (int)($n * 1024);
    return (int)$n;
}

/**
 * Resize an oversized upload in place and re-encode it.
 *
 * Quietly does nothing when GD is unavailable or the file is already small,
 * so an upload never fails just because the image could not be optimised.
 *
 * @return bool true when the file was rewritten
 */
function optimiseImageFile($path, $maxWidth = 1600, $quality = 84) {
    if (!function_exists('imagecreatetruecolor')) return false;
    if (!is_file($path)) return false;

    $info = @getimagesize($path);
    if (!$info) return false;

    [$width, $height] = $info;
    $mime = $info['mime'] ?? '';

    // Animated GIFs would lose their frames, so leave them alone.
    if ($mime === 'image/gif') return false;

    // A photo straight off a phone is usually rotated by an EXIF tag rather
    // than in the pixels. GD ignores that tag, so a resized copy would come out
    // lying on its side. Work out whether we have to turn it ourselves.
    $rotate = 0;
    $flip   = false;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        switch ((int)($exif['Orientation'] ?? 1)) {
            case 2: $flip = true;                 break;
            case 3: $rotate = 180;                break;
            case 4: $rotate = 180; $flip = true;  break;
            case 5: $rotate = 270; $flip = true;  break;
            case 6: $rotate = 270;                break;
            case 7: $rotate = 90;  $flip = true;  break;
            case 8: $rotate = 90;                 break;
        }
    }

    // Nothing to do: already web-sized, small enough, and the right way up.
    if ($width <= $maxWidth && filesize($path) < 350 * 1024 && !$rotate && !$flip) return false;

    // Decoding happens into an uncompressed bitmap: 4 bytes a pixel, plus room
    // for the resized copy and normal request overhead. Running out here is a
    // fatal error that would take the whole product save down with it, so ask
    // for the headroom first and give up quietly if we cannot have it.
    $needed = (int)($width * $height * 4 * 2.2) + (32 * 1024 * 1024);
    $limit  = imageMemoryLimitBytes();
    if ($limit > 0 && $limit < $needed) {
        @ini_set('memory_limit', (int)ceil($needed / (1024 * 1024)) . 'M');
        $limit = imageMemoryLimitBytes();
        if ($limit > 0 && $limit < $needed) return false; // host won't allow it
    }

    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png':  $src = @imagecreatefrompng($path);  break;
        case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null; break;
        default: return false;
    }
    if (!$src) return false;

    // Straighten it before measuring, so a portrait photo is scaled by its real
    // width rather than the width it happens to be stored at.
    if ($rotate) {
        $turned = @imagerotate($src, $rotate, 0);
        if ($turned) { imagedestroy($src); $src = $turned; }
    }
    if ($flip && function_exists('imageflip')) {
        imageflip($src, IMG_FLIP_HORIZONTAL);
    }
    $width  = imagesx($src);
    $height = imagesy($src);

    $newWidth  = min($width, $maxWidth);
    $newHeight = (int)round($height * ($newWidth / $width));

    $dst = imagecreatetruecolor($newWidth, $newHeight);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    } else {
        // Flatten onto white so a transparent source does not go black.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $ok = false;
    switch ($mime) {
        // Baseline rather than progressive: progressive JPEGs are what make a
        // slow image appear to load in bands.
        case 'image/jpeg': imageinterlace($dst, false); $ok = imagejpeg($dst, $path, $quality); break;
        case 'image/png':  $ok = imagepng($dst, $path, 6); break;
        case 'image/webp': $ok = function_exists('imagewebp') ? imagewebp($dst, $path, $quality) : false; break;
    }

    imagedestroy($src);
    imagedestroy($dst);
    return (bool)$ok;
}

/**
 * Minimal SMTP client - no Composer, no PHPMailer.
 *
 * Shared cPanel hosting has no `composer install` step and vendor/ is not in
 * the repo, so the PHPMailer branch below never runs in production. This talks
 * SMTP directly so mail is sent authenticated as the real mailbox (which keeps
 * SPF/DKIM aligned) instead of falling back to a bare mail() call.
 *
 * @return array{ok:bool, error:string}
 */
function smtpSend($to, $subject, $htmlBody) {
    $host = SMTP_HOST;
    $port = (int)SMTP_PORT;
    $user = SMTP_USERNAME;
    $pass = SMTP_PASSWORD;
    $enc  = strtolower(SMTP_ENCRYPTION);

    if ($host === '' || $user === '' || $pass === '') {
        return ['ok' => false, 'error' => 'SMTP not configured'];
    }

    $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ]]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return ['ok' => false, 'error' => "connect failed: $errstr ($errno)"];
    stream_set_timeout($fp, 20);

    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function ($line, $expect) use ($fp, $read) {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $res  = $read();
        $code = (int)substr(ltrim($res), 0, 3);
        return in_array($code, (array)$expect, true) ? ['ok' => true, 'res' => $res]
                                                     : ['ok' => false, 'res' => trim($res)];
    };

    $host_name = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';

    // Each step is checked as it happens. Running the whole conversation and
    // only then looking for a failure means a rejected password is followed by
    // MAIL FROM and DATA, which confuses the server and buries the real cause.
    $run = function ($label, $line, $expect) use ($cmd, $fp) {
        $r = $cmd($line, $expect);
        if (!$r['ok']) {
            fclose($fp);
            return ['ok' => false, 'error' => $label . ' rejected: ' . $r['res']];
        }
        return ['ok' => true];
    };

    foreach ([['greeting', null, 220], ['EHLO', 'EHLO ' . $host_name, 250]] as $st) {
        $r = $run($st[0], $st[1], $st[2]);
        if (!$r['ok']) return $r;
    }

    if ($enc === 'tls') {
        $r = $run('STARTTLS', 'STARTTLS', 220);
        if (!$r['ok']) return $r;
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return ['ok' => false, 'error' => 'STARTTLS negotiation failed'];
        }
        $r = $run('EHLO after STARTTLS', 'EHLO ' . $host_name, 250);
        if (!$r['ok']) return $r;
    }

    foreach ([
        ['AUTH LOGIN',  'AUTH LOGIN',                            334],
        ['username',    base64_encode($user),                    334],
        ['password',    base64_encode($pass),                    235],
        ['MAIL FROM',   'MAIL FROM:<' . SMTP_FROM_EMAIL . '>',   250],
        ['RCPT TO',     'RCPT TO:<' . $to . '>',                 [250, 251]],
        ['DATA',        'DATA',                                  354],
    ] as $st) {
        $r = $run($st[0], $st[1], $st[2]);
        if (!$r['ok']) return $r;
    }

    $boundary = 'phelyz_' . bin2hex(random_bytes(8));
    $headers  = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= 'To: <' . $to . ">\r\n";
    $headers .= 'Subject: ' . $subject . "\r\n";
    $headers .= 'Date: ' . date('r') . "\r\n";
    $headers .= 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $host_name . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";

    $plain = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));
    $body  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$plain\r\n";
    $body .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n$htmlBody\r\n";
    $body .= "--$boundary--\r\n";

    // Dot-stuffing: a line that is just "." would otherwise end the message
    $data = preg_replace('/^\./m', '..', $headers . "\r\n" . $body);
    fwrite($fp, $data . "\r\n.\r\n");

    $final = $cmd(null, 250);
    $cmd('QUIT', [221, 250]);
    fclose($fp);

    return $final['ok'] ? ['ok' => true, 'error' => '']
                        : ['ok' => false, 'error' => 'message rejected: ' . $final['res']];
}

/**
 * Wrap email content in the Phelyz shell: logo header, white card, footer.
 *
 * Built with tables and inline styles because that is all the older mail
 * clients reliably support. The logo is a PNG rather than the site's SVG:
 * Gmail and Outlook refuse to render SVG in a message body.
 *
 * @param string $bodyHtml   The message body (already HTML).
 * @param string $preheader  Short line shown in the inbox preview.
 */
function phelyzEmailTemplate($bodyHtml, $preheader = '') {
    $logo = SITE_URL . '/assets/images/phelyz-logo-email.png';
    $year = date('Y');
    $site = htmlspecialchars(SITE_NAME);
    $pre  = htmlspecialchars($preheader);

    return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
      . '<meta name="viewport" content="width=device-width,initial-scale=1">'
      . '</head>'
      . '<body style="margin:0;padding:0;background:#F5F5F4;">'
      . ($pre !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $pre . '</div>' : '')
      . '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F5F4;padding:32px 16px;">'
      . '<tr><td align="center">'
      . '<table width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.07);font-family:Arial,Helvetica,sans-serif;">'

      // Header with the logo
      . '<tr><td align="center" style="padding:30px 32px 22px;background:#ffffff;">'
      . '<img src="' . $logo . '" alt="' . $site . '" width="180" '
      . 'style="display:block;width:180px;max-width:60%;height:auto;border:0;outline:none;text-decoration:none;">'
      . '</td></tr>'
      . '<tr><td style="height:3px;background:#CA8A04;font-size:0;line-height:0;">&nbsp;</td></tr>'

      // Body
      . '<tr><td style="padding:34px 32px 30px;color:#1C1917;font-size:15px;line-height:1.7;">'
      . $bodyHtml
      . '</td></tr>'

      // Footer
      . '<tr><td style="background:#FAFAF9;border-top:1px solid #E7E5E4;padding:20px 32px;text-align:center;">'
      . '<p style="margin:0 0 6px;color:#78716C;font-size:12px;line-height:1.6;">'
      . $site . ' &middot; ' . htmlspecialchars(SITE_ADDRESS) . '</p>'
      . '<p style="margin:0;color:#A8A29E;font-size:11px;">&copy; ' . $year . ' ' . $site . '. All rights reserved.</p>'
      . '</td></tr>'

      . '</table></td></tr></table></body></html>';
}

/** A gold call-to-action button that survives Outlook. */
function phelyzEmailButton($text, $url) {
    return '<table cellpadding="0" cellspacing="0" border="0" align="center" style="margin:26px auto;">'
      . '<tr><td align="center" bgcolor="#CA8A04" style="border-radius:8px;">'
      . '<a href="' . htmlspecialchars($url) . '" target="_blank" '
      . 'style="display:inline-block;padding:15px 38px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;'
      . 'font-size:15px;font-weight:bold;text-decoration:none;border-radius:8px;">'
      . htmlspecialchars($text) . '</a></td></tr></table>';
}

/**
 * Context for the next email, so the log knows what it was and why.
 *
 * Set immediately before calling sendEmail(). Cleared automatically after,
 * so a stray value can never mislabel a later message.
 */
$GLOBALS['PHELYZ_EMAIL_CONTEXT'] = [];

function emailContext(array $ctx) {
    $GLOBALS['PHELYZ_EMAIL_CONTEXT'] = $ctx;
}

/** A short, quotable reference for one message. */
function emailToken() {
    return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(24))), 0, 24);
}

/**
 * Record one email. Called from inside sendEmail() so nothing can be sent
 * without leaving a trace, including anything added to the site later.
 *
 * @return string|null the token, used to build the open-tracking pixel
 */
function logEmail($to, $subject, $body, $status, $transport = null, $error = null) {
    $ctx = $GLOBALS['PHELYZ_EMAIL_CONTEXT'] ?? [];
    $token = emailToken();

    $userId = null;
    $subscribed = 1;
    try {
        $db = getDB();
        $u = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$to]);
        $userId = $u['id'] ?? null;
        $opt = $db->fetchOne("SELECT id FROM email_unsubscribes WHERE email = ?", [strtolower($to)]);
        $subscribed = $opt ? 0 : 1;
    } catch (Exception $e) {}

    try {
        getDB()->insert('email_log', [
            'token'          => $token,
            'to_email'       => $to,
            'to_name'        => $ctx['to_name']     ?? null,
            'subject'        => mb_substr($subject, 0, 255),
            'body_html'      => $body,
            'category'       => $ctx['category']    ?? 'other',
            'source_type'    => $ctx['source_type'] ?? null,
            'source_id'      => isset($ctx['source_id']) ? (string)$ctx['source_id'] : null,
            'audience'       => $ctx['audience']    ?? null,
            'status'         => $status,
            'transport'      => $transport,
            'error'          => $error ? mb_substr($error, 0, 255) : null,
            'user_id'        => $userId,
            'was_subscribed' => $subscribed,
        ]);
        return $token;
    } catch (Exception $e) {
        // The log must never stop mail going out.
        return null;
    }
}

/** Append the invisible open-tracking pixel to an HTML message. */
function emailWithPixel($html, $token) {
    if (!$token) return $html;
    $pixel = '<img src="' . SITE_URL . '/api/email-open.php?m=' . urlencode($token)
           . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;">';
    if (stripos($html, '</body>') !== false) {
        return str_ireplace('</body>', $pixel . '</body>', $html);
    }
    return $html . $pixel;
}

/**
 * Send an email and record it.
 *
 * Every message in the store goes through here, so this is the one place that
 * can guarantee a complete history. The tracking pixel is added before the
 * message leaves, using the token minted by the log row.
 */
function sendEmail($to, $subject, $message) {
    $token = logEmail($to, $subject, $message, 'sent');
    $body  = emailWithPixel($message, $token);

    $GLOBALS['PHELYZ_SMTP_FALLBACK'] = null;
    $result = sendEmailRaw($to, $subject, $body);

    // A message that went out on a fallback route still counts as sent, but the
    // reason the preferred route failed is worth keeping on the row.
    if ($result['ok'] && !empty($GLOBALS['PHELYZ_SMTP_FALLBACK'])) {
        $result['error'] = 'Fell back from SMTP: ' . $GLOBALS['PHELYZ_SMTP_FALLBACK'];
    }

    // Correct the row if the send actually failed, and note which transport
    // carried it so a deliverability problem can be traced to one route.
    if ($token) {
        try {
            getDB()->update('email_log', [
                'status'    => $result['ok'] ? 'sent' : 'failed',
                'transport' => $result['transport'],
                'error'     => $result['error'] ? mb_substr($result['error'], 0, 255) : null,
            ], 'token = ?', [$token]);
        } catch (Exception $e) {}
    }

    $GLOBALS['PHELYZ_EMAIL_CONTEXT'] = [];   // never leak into the next message
    return $result['ok'];
}

/**
 * The actual delivery, tried in order of preference.
 * @return array ok, transport, error
 */
function sendEmailRaw($to, $subject, $message) {
    // Preferred on cPanel: authenticated SMTP through the domain mailbox.
    if (SMTP_HOST !== '' && SMTP_USERNAME !== '' && SMTP_PASSWORD !== '') {
        $r = smtpSend($to, $subject, $message);
        if ($r['ok']) return ['ok' => true, 'transport' => 'smtp', 'error' => null];
        error_log('SMTP send failed: ' . $r['error']);
        // Remember why. Falling back keeps the mail flowing, but a primary
        // transport that is quietly broken costs deliverability, and without
        // this the reason never reaches anywhere anyone looks.
        $GLOBALS['PHELYZ_SMTP_FALLBACK'] = $r['error'];
        // fall through to the other transports rather than silently dropping mail
    }

    // Use Resend API if key is set
    if (!empty(RESEND_API_KEY)) {
        $payload = [
            'from'    => SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $message,
            'text'    => strip_tags($message),
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . RESEND_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 || $code === 201) return ['ok' => true, 'transport' => 'resend', 'error' => null];
        error_log('Resend error: ' . $response);
        return ['ok' => false, 'transport' => 'resend', 'error' => substr((string)$response, 0, 200)];
    }

    // PHPMailer fallback (local dev with Composer)
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            $mail->send();
            return ['ok' => true, 'transport' => 'phpmailer', 'error' => null];
        } catch (Exception $e) {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            return ['ok' => false, 'transport' => 'phpmailer', 'error' => $mail->ErrorInfo];
        }
    }

    // Basic mail() last resort
    $headers  = "From: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $sent = mail($to, $subject, $message, $headers);
    return ['ok' => (bool)$sent, 'transport' => 'mail', 'error' => $sent ? null : 'mail() returned false'];
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'processing' => '<span class="badge badge-info">Processing</span>',
        'shipped' => '<span class="badge badge-primary">Shipped</span>',
        'delivered' => '<span class="badge badge-success">Delivered</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>'
    ];
    
    return $badges[$status] ?? $status;
}

function pagination($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<ul class="pagination">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $url . '&page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Pages
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'class="active"' : '';
        $html .= '<li ' . $active . '><a href="' . $url . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $url . '&page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}
// ===========================================
// REVIEW FUNCTIONS
// ===========================================

function getProductReviews($productId, $limit = null) {
    $db = getDB();
    $sql = "SELECT r.*, u.first_name, u.last_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? 
            ORDER BY r.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    return $db->fetchAll($sql, [$productId]);
}

function getReviewStats($productId) {
    $db = getDB();
    
    // Get average rating and total count
    $stats = $db->fetchOne(
        "SELECT 
            COALESCE(AVG(rating), 0) as average,
            COUNT(*) as total
        FROM reviews 
        WHERE product_id = ?",
        [$productId]
    );
    
    // Get rating breakdown (count for each star level)
    $breakdown = [];
    for ($i = 1; $i <= 5; $i++) {
        $count = $db->fetchOne(
            "SELECT COUNT(*) as count FROM reviews WHERE product_id = ? AND rating = ?",
            [$productId, $i]
        );
        $breakdown[$i] = $count['count'] ?? 0;
    }
    
    return [
        'average' => (float)$stats['average'],
        'total' => (int)$stats['total'],
        'breakdown' => $breakdown
    ];
}

function hasUserPurchasedProduct($userId, $productId) {
    $db = getDB();
    
    // Check if user has a completed/delivered order containing this product
    $result = $db->fetchOne(
        "SELECT oi.id 
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.user_id = ? 
         AND oi.product_id = ?
         AND o.status IN ('delivered', 'completed')
         LIMIT 1",
        [$userId, $productId]
    );
    
    return $result !== null;
}

function updateProductRating($productId) {
    $db = getDB();
    
    // Calculate new average rating and review count
    $stats = $db->fetchOne(
        "SELECT 
            COALESCE(AVG(rating), 0) as average,
            COUNT(*) as count
        FROM reviews 
        WHERE product_id = ?",
        [$productId]
    );
    
    // Update product table
    $db->update(
        'products',
        [
            'rating' => round($stats['average'], 2),
            'review_count' => $stats['count']
        ],
        'id = ?',
        [$productId]
    );
}

function deleteReview($reviewId, $userId) {
    $db = getDB();
    
    // Get product_id before deleting
    $review = $db->fetchOne("SELECT product_id FROM reviews WHERE id = ? AND user_id = ?", [$reviewId, $userId]);
    
    if (!$review) {
        return false;
    }
    
    // Delete review
    $deleted = $db->delete('reviews', 'id = ? AND user_id = ?', [$reviewId, $userId]);
    
    if ($deleted) {
        // Update product rating
        updateProductRating($review['product_id']);
        return true;
    }
    
    return false;
}
?>