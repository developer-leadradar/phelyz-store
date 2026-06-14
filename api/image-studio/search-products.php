<?php
/**
 * Lightweight admin product search for the Image Studio "assign" autocomplete.
 * Returns up to 20 products matching ?q=.
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
requireAdmin();

$q = trim($_GET['q'] ?? '');
$db = getDB();

if (strlen($q) < 1) {
    $rows = $db->fetchAll("SELECT id, name, sku, image FROM products ORDER BY created_at DESC LIMIT 20");
} else {
    $like = '%' . $q . '%';
    $rows = $db->fetchAll(
        "SELECT id, name, sku, image FROM products
         WHERE name LIKE ? OR sku LIKE ?
         ORDER BY name ASC LIMIT 20",
        [$like, $like]
    );
}

echo json_encode(['products' => $rows]);
