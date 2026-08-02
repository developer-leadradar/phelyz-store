<?php
/**
 * Infinite-scroll feed for the shop grid.
 *
 * Accepts the same GET filters as shop.php plus ?page=N and returns the
 * rendered cards for that page, using the shared product-card partial so the
 * markup matches the server-rendered first page exactly.
 *
 * Response: { ok, html, has_more, page, total }
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/product-card.php';

header('Content-Type: application/json');

// Same filter parsing as shop.php
$filters = [];
if (!empty($_GET['category']))     $filters['category_id'] = (int)$_GET['category'];
if (!empty($_GET['search']))       $filters['search']       = sanitize($_GET['search']);
if (!empty($_GET['min_price']))    $filters['min_price']    = (float)$_GET['min_price'];
if (!empty($_GET['max_price']))    $filters['max_price']    = (float)$_GET['max_price'];
if (!empty($_GET['material']))     $filters['material']     = sanitize($_GET['material']);
if (!empty($_GET['metal_purity'])) $filters['metal_purity'] = sanitize($_GET['metal_purity']);
if (!empty($_GET['stone_type']))   $filters['stone_type']   = sanitize($_GET['stone_type']);
if (!empty($_GET['brand']))        $filters['brand']        = sanitize($_GET['brand']);
if (!empty($_GET['gender']))       $filters['gender']       = sanitize($_GET['gender']);
if (!empty($_GET['style']))        $filters['style']        = sanitize($_GET['style']);
if (!empty($_GET['occasion']))     $filters['occasion']     = sanitize($_GET['occasion']);
if (isset($_GET['in_stock']))      $filters['in_stock']     = true;
if (isset($_GET['featured']))      $filters['featured']     = true;
if (!empty($_GET['rating']))       $filters['min_rating']   = (float)$_GET['rating'];
if (!empty($_GET['sort']))         $filters['sort']         = sanitize($_GET['sort']);

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

try {
    $products = getAllProducts($filters, $perPage, $offset);
    $total    = countProducts($filters);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Could not load products.']);
    exit;
}

ob_start();
foreach ($products as $p) {
    renderProductCard($p);
}
$html = ob_get_clean();

echo json_encode([
    'ok'       => true,
    'html'     => $html,
    'page'     => $page,
    'total'    => (int)$total,
    'has_more' => ($offset + count($products)) < (int)$total,
]);
