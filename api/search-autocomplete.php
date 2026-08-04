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

/**
 * Look up products and categories for one search term.
 *
 * @return array [products, categories]
 */
function autocompleteLookup($db, $query) {
    $stems       = searchStems($query);
    $params      = [];
    $likeClauses = [];
    foreach ($stems as $stem) {
        $likeClauses[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
        $params[] = '%' . $stem . '%';
        $params[] = '%' . $stem . '%';
        $params[] = '%' . $stem . '%';
    }
    $whereOr = implode(' OR ', $likeClauses);

    // Ordering params: name starting with the term first, then containing it
    $params[] = $query . '%';
    $params[] = '%' . $query . '%';

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

    $categories = $db->fetchAll(
        "SELECT id, name, slug
         FROM categories
         WHERE is_active = 1
         AND name LIKE ?
         LIMIT 5",
        ['%' . $query . '%']
    );

    return [$products, $categories];
}

[$products, $categories] = autocompleteLookup($db, $query);

// Nothing matched? The shopper has most likely mistyped, so fall back to the
// closest real words in the catalogue rather than showing an empty dropdown.
$correctedFrom = '';
if (!$products && !$categories) {
    [$corrected, $didChange] = searchCorrectQuery($query);
    if ($didChange) {
        [$altProducts, $altCategories] = autocompleteLookup($db, $corrected);
        if ($altProducts || $altCategories) {
            $correctedFrom = $query;
            $query         = $corrected;
            $products      = $altProducts;
            $categories    = $altCategories;
        }
    }
}

// Format results
$results = [
    'success'       => true,
    'query'         => $query,
    'correctedFrom' => $correctedFrom,
    'products'      => [],
    'categories'    => []
];

foreach ($products as $product) {
    $results['products'][] = [
        'id'    => $product['id'],
        'name'  => $product['name'],
        'slug'  => $product['slug'],
        'price' => formatPrice($product['price']),
        'image' => $product['image'],
        'url'   => 'product.php?id=' . $product['id']
    ];
}

foreach ($categories as $category) {
    $results['categories'][] = [
        'id'   => $category['id'],
        'name' => $category['name'],
        'slug' => $category['slug'],
        'url'  => 'shop.php?category=' . $category['id']
    ];
}

echo json_encode($results);
