<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Build filters from GET parameters
$filters = [];

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $filters['category_id'] = (int)$_GET['category'];
}

if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $filters['min_price'] = (float)$_GET['min_price'];
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $filters['max_price'] = (float)$_GET['max_price'];
}

if (isset($_GET['material']) && !empty($_GET['material'])) {
    $filters['material'] = sanitize($_GET['material']);
}

if (isset($_GET['metal_purity']) && !empty($_GET['metal_purity'])) {
    $filters['metal_purity'] = sanitize($_GET['metal_purity']);
}

if (isset($_GET['stone_type']) && !empty($_GET['stone_type'])) {
    $filters['stone_type'] = sanitize($_GET['stone_type']);
}

if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $filters['brand'] = sanitize($_GET['brand']);
}

if (isset($_GET['gender']) && !empty($_GET['gender'])) {
    $filters['gender'] = sanitize($_GET['gender']);
}

if (isset($_GET['style']) && !empty($_GET['style'])) {
    $filters['style'] = sanitize($_GET['style']);
}

if (isset($_GET['in_stock'])) {
    $filters['in_stock'] = true;
}

if (isset($_GET['featured'])) {
    $filters['featured'] = true;
}

if (isset($_GET['rating']) && !empty($_GET['rating'])) {
    $filters['min_rating'] = (float)$_GET['rating'];
}

if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    $filters['sort'] = sanitize($_GET['sort']);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$offset = ($page - 1) * $perPage;

// Get products
$products = getAllProducts($filters, $perPage, $offset);
$totalProducts = countProducts($filters);
$totalPages = ceil($totalProducts / $perPage);

// Format products for JSON response
$formattedProducts = [];
foreach ($products as $product) {
    $formattedProducts[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'category_name' => $product['category_name'],
        'price' => $product['price'],
        'compare_price' => $product['compare_price'],
        'image' => $product['image'],
        'rating' => $product['rating'],
        'review_count' => $product['review_count'],
        'stock_quantity' => $product['stock_quantity'],
        'is_featured' => $product['is_featured'],
        'material' => $product['material'],
        'metal_purity' => $product['metal_purity'],
        'stone_type' => $product['stone_type'],
        'stone_weight' => $product['stone_weight']
    ];
}

echo json_encode([
    'success' => true,
    'products' => $formattedProducts,
    'total' => $totalProducts,
    'page' => $page,
    'total_pages' => $totalPages,
    'per_page' => $perPage
]);
?>