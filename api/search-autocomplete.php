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

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

$db = getDB();

// Build stem variants for plural/singular matching
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
$stems = array_unique($stems);

// Build OR conditions for each stem
$likeClauses = [];
$params = [];
foreach ($stems as $stem) {
    $likeClauses[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $params[] = '%' . $stem . '%';
    $params[] = '%' . $stem . '%';
    $params[] = '%' . $stem . '%';
}
$whereOr = implode(' OR ', $likeClauses);

// Ordering params (use primary query stem)
$params[] = $query . '%';
$params[] = '%' . $query . '%';

// Search products
$products = $db->fetchAll(
    "SELECT id, name, slug, price, image, category_id
     FROM products
     WHERE is_active = 1
     AND ($whereOr)
     ORDER BY
         CASE
             WHEN name LIKE ? THEN 1
             WHEN name LIKE ? THEN 2
             ELSE 3
         END,
         name ASC
     LIMIT 10",
    $params
);

// Search categories
$categories = $db->fetchAll(
    "SELECT id, name, slug 
     FROM categories 
     WHERE is_active = 1 
     AND name LIKE ?
     LIMIT 5",
    ['%' . $query . '%']
);

// Format results
$results = [
    'success' => true,
    'query' => $query,
    'products' => [],
    'categories' => []
];

foreach ($products as $product) {
    $results['products'][] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => formatPrice($product['price']),
        'image' => $product['image'],
        'url' => 'product.php?id=' . $product['id']
    ];
}

foreach ($categories as $category) {
    $results['categories'][] = [
        'id' => $category['id'],
        'name' => $category['name'],
        'slug' => $category['slug'],
        'url' => 'shop.php?category=' . $category['id']
    ];
}

echo json_encode($results);
?>