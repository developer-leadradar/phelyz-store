<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

// Accept both FormData (multipart) and JSON
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;

if (!isset($data['product_id']) || !isset($data['rating']) || !isset($data['review_text'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId     = (int)$_SESSION['user_id'];
$productId  = (int)$data['product_id'];
$rating     = (int)$data['rating'];
$reviewText = trim($data['review_text']);
$reviewId   = !empty($data['review_id']) ? (int)$data['review_id'] : 0;

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

if (strlen($reviewText) < 10) {
    echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
    exit;
}

if (strlen($reviewText) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Review must be less than 1000 characters']);
    exit;
}

$db = getDB();

// ── Edit existing review ──────────────────────────────────
if ($reviewId > 0) {
    $existing = $db->fetchOne(
        "SELECT id, product_id FROM reviews WHERE id = ? AND user_id = ?",
        [$reviewId, $userId]
    );

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Review not found or not yours']);
        exit;
    }

    $updated = $db->update('reviews', [
        'rating'      => $rating,
        'review_text' => $reviewText,
    ], 'id = ? AND user_id = ?', [$reviewId, $userId]);

    if ($updated !== false) {
        updateProductRating($existing['product_id']);
        echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update review']);
    }
    exit;
}

// ── New review ────────────────────────────────────────────
if (!hasUserPurchasedProduct($userId, $productId)) {
    echo json_encode(['success' => false, 'message' => 'You must purchase this product before reviewing']);
    exit;
}

$existingReview = $db->fetchOne(
    "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?",
    [$userId, $productId]
);

if ($existingReview) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product. Use the Edit button on your review.']);
    exit;
}

$newId = $db->insert('reviews', [
    'user_id'           => $userId,
    'product_id'        => $productId,
    'rating'            => $rating,
    'review_text'       => $reviewText,
    'verified_purchase' => 1,
]);

if ($newId) {
    updateProductRating($productId);
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>
