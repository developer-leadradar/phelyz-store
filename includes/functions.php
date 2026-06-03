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
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('admin/login.php');
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
            case 'price_low':
                $orderBy = "p.price ASC";
                break;
            case 'price_high':
                $orderBy = "p.price DESC";
                break;
            case 'name_asc':
                $orderBy = "p.name ASC";
                break;
            case 'name_desc':
                $orderBy = "p.name DESC";
                break;
            case 'popular':
                $orderBy = "p.review_count DESC, p.rating DESC";
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
         ORDER BY RAND() 
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

function addToCart($productId, $quantity = 1) {
    $db = getDB();
    $cart = getOrCreateCart();
    
    // Check if product exists
    $product = getProductById($productId);
    if (!$product) {
        return false;
    }
    
    // Check stock
    if ($product['stock_quantity'] < $quantity) {
        return false;
    }
    
    // Check if item already in cart
    $existingItem = $db->fetchOne(
        "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?",
        [$cart['id'], $productId]
    );
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock_quantity']) {
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
            'quantity' => $quantity
        ]);
    }
}

function getCartItems() {
    $db = getDB();
    $cart = getOrCreateCart();
    
    return $db->fetchAll(
        "SELECT ci.*, p.name, p.price, p.image, p.stock_quantity 
         FROM cart_items ci 
         JOIN products p ON ci.product_id = p.id 
         WHERE ci.cart_id = ?",
        [$cart['id']]
    );
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

function createOrder($orderData) {
    $db = getDB();
    
    // Generate order number
    $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
            'price_at_purchase' => $item['price'],
            'subtotal' => $item['price'] * $item['quantity']
        ]);
        
        // Reduce stock
        $db->query(
            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
            [$item['quantity'], $item['product_id']]
        );
    }
    
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
    ob_end_clean();  
    header("Location: $url");
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

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
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

function uploadImage($file, $directory = 'products') {
    $uploadDir = UPLOAD_PATH . $directory . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $newFilename = uniqid() . '.' . $ext;
    $destination = $uploadDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $directory . '/' . $newFilename;
    }
    
    return false;
}

function sendEmail($to, $subject, $message) {
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($autoload)) {
        // Localhost fallback — basic mail()
        $headers  = "From: " . SITE_EMAIL . "\r\n";
        $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    require_once $autoload;
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Sender & Recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Plain-text fallback

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
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