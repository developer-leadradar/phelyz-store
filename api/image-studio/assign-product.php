<?php
/**
 * Approves an Image Studio output and assigns it to a product.
 *
 * Input JSON: { output_id, product_id, role ('primary'|'gallery') }
 * Output JSON: { ok, message }
 *
 * If role = 'primary', the product's main `image` is replaced and the previous
 * primary is bumped into the gallery (product_images). If role = 'gallery',
 * the output is appended to product_images at the end of sort_order.
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$outputId  = (int)($data['output_id']  ?? 0);
$productId = (int)($data['product_id'] ?? 0);
$role      = in_array(($data['role'] ?? 'gallery'), ['primary', 'gallery'], true) ? $data['role'] : 'gallery';

if ($outputId <= 0 || $productId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'output_id and product_id are required.']);
    exit;
}

$db = getDB();

$output  = $db->fetchOne("SELECT * FROM image_studio_outputs WHERE id = ?", [$outputId]);
$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
if (!$output)  { echo json_encode(['ok' => false, 'message' => 'Output not found.']); exit; }
if (!$product) { echo json_encode(['ok' => false, 'message' => 'Product not found.']); exit; }

try {
    if ($role === 'primary') {
        // Push the old primary into gallery (if it exists) then swap
        if (!empty($product['image'])) {
            $maxSort = $db->fetchOne(
                "SELECT COALESCE(MAX(sort_order),0) AS s FROM product_images WHERE product_id = ?",
                [$productId]
            );
            $db->insert('product_images', [
                'product_id' => $productId,
                'image_path' => $product['image'],
                'sort_order' => (int)($maxSort['s'] ?? 0) + 1,
                'is_primary' => 0,
            ]);
        }
        $db->update('products', ['image' => $output['output_path']], 'id = ?', [$productId]);
    } else {
        $maxSort = $db->fetchOne(
            "SELECT COALESCE(MAX(sort_order),0) AS s FROM product_images WHERE product_id = ?",
            [$productId]
        );
        $db->insert('product_images', [
            'product_id' => $productId,
            'image_path' => $output['output_path'],
            'sort_order' => (int)($maxSort['s'] ?? 0) + 1,
            'is_primary' => 0,
        ]);
    }

    $db->update('image_studio_outputs', [
        'status' => 'approved',
        'assigned_product_id' => $productId,
    ], 'id = ?', [$outputId]);

    echo json_encode(['ok' => true, 'message' => 'Image attached to product.']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
