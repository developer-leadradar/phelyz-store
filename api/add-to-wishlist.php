<?php
/**
 * Add, remove or toggle a wishlist item.
 *
 * The browser reads this with res.json(), so it must reply with JSON whatever
 * happens. Anything else - a stray PHP warning, a fatal, an HTML error page -
 * makes res.json() throw, and the shopper is told "Network error" while the
 * real reason is lost. Hence the output buffer and the catch-all below.
 *
 * The reply always reports the resulting state in `in_wishlist`, so the button
 * can show what is actually true rather than guessing from the action it asked
 * for.
 */
define('PHELYZ_ACCESS', true);

// Nothing may reach the browser except the JSON at the end.
ob_start();

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

/** Send the reply and stop, discarding anything else that was printed. */
function wishlistReply(array $payload) {
    if (ob_get_length() !== false) ob_end_clean();
    if (!headers_sent()) header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wishlistReply(['success' => false, 'message' => 'Invalid request']);
    }

    if (!isLoggedIn()) {
        wishlistReply([
            'success'      => false,
            'requires_login' => true,
            'message'      => 'Please sign in to save items',
        ]);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || !isset($data['product_id'])) {
        wishlistReply(['success' => false, 'message' => 'Product ID required']);
    }

    $productId = (int)$data['product_id'];
    $action    = isset($data['action']) ? $data['action'] : 'add';

    if ($productId <= 0) {
        wishlistReply(['success' => false, 'message' => 'Product ID required']);
    }

    // Work out what to do from what is actually in the wishlist right now.
    $wasSaved = isInWishlist($productId);
    if ($action === 'toggle') {
        $action = $wasSaved ? 'remove' : 'add';
    }

    if ($action === 'remove') {
        // Already gone counts as done - the shopper wanted it off the list and
        // it is off the list.
        $ok = $wasSaved ? removeFromWishlist($productId) : true;
        wishlistReply($ok
            ? ['success' => true, 'action' => 'removed', 'in_wishlist' => false,
               'message' => 'Removed from wishlist']
            : ['success' => false, 'in_wishlist' => isInWishlist($productId),
               'message' => 'Could not remove that item']);
    }

    $ok = $wasSaved ? true : (bool)addToWishlist($productId);
    wishlistReply($ok
        ? ['success' => true, 'action' => 'added', 'in_wishlist' => true,
           'message' => 'Added to wishlist']
        : ['success' => false, 'in_wishlist' => false,
           'message' => 'Could not save that item']);

} catch (Throwable $e) {
    error_log('add-to-wishlist failed: ' . $e->getMessage());
    wishlistReply(['success' => false, 'message' => 'Something went wrong saving that item']);
}
