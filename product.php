<?php
require_once 'includes/header.php';

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    redirect('shop.php');
}

// Get product details
$product = getProductById($productId);

if (!$product) {
    redirect('shop.php');
}

$pageTitle = $product['name'];
$pageDescription = substr(strip_tags($product['description']), 0, 160);

// Get related products
$relatedProducts = getRelatedProducts($product['id'], $product['category_id'], 4);

// Check if in wishlist
$inWishlist = isInWishlist($productId);

// Get reviews
$reviews = getProductReviews($productId);
$reviewStats = getReviewStats($productId);
$userHasPurchased = isLoggedIn() ? hasUserPurchasedProduct($_SESSION['user_id'], $productId) : false;

function renderStars($rating) {
  $out = '';
  for ($i = 1; $i <= 5; $i++) {
    $filled = $i <= $rating;
    $out .= '<svg class="'.($filled?'star-on':'star-off').'" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
  }
  return $out;
}

// Parse additional images
$additionalImages = [];
if (!empty($product['images'])) {
    $additionalImages = json_decode($product['images'], true) ?? [];
}

// Discount percent
$discountPct = ($product['compare_price'] > $product['price'] && $product['compare_price'] > 0)
    ? round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100)
    : 0;
?>

<div class="bg-cream min-h-screen">
  <div class="container py-8">

    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-8">
      <a href="index.php">Home</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-stone-400"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <a href="shop.php">Shop</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-stone-400"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <a href="shop.php?category=<?php echo (int)$product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-stone-400"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <span><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <!-- ── Product Detail ─────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-16">

      <!-- Left: Gallery -->
      <div class="lg:sticky lg:top-24 self-start">
        <!-- Main Image -->
        <div class="relative bg-white rounded-2xl overflow-hidden border border-stone-200 shadow-sm mb-3 aspect-square flex items-center justify-center p-8">
          <img src="<?php echo htmlspecialchars($product['image']); ?>"
               alt="<?php echo htmlspecialchars($product['name']); ?>"
               id="main-product-image"
               class="max-w-full max-h-full object-contain transition-transform duration-500 hover:scale-105">

          <?php if ($discountPct > 0): ?>
            <span class="product-card-badge badge-sale"><?php echo $discountPct; ?>% OFF</span>
          <?php elseif (!empty($product['is_new'])): ?>
            <span class="product-card-badge badge-new">New</span>
          <?php endif; ?>
        </div>

        <!-- Thumbnails -->
        <?php if (!empty($additionalImages)): ?>
        <div class="flex gap-2 overflow-x-auto pb-1">
          <button onclick="setMainImage('<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>', this)"
            class="thumb-btn active flex-shrink-0 w-16 h-16 rounded-lg border-2 border-gold p-1 bg-white overflow-hidden">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Main" class="w-full h-full object-contain">
          </button>
          <?php foreach ($additionalImages as $img): ?>
            <button onclick="setMainImage('<?php echo htmlspecialchars($img, ENT_QUOTES); ?>', this)"
              class="thumb-btn flex-shrink-0 w-16 h-16 rounded-lg border-2 border-stone-200 hover:border-gold p-1 bg-white overflow-hidden transition-colors">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="View" class="w-full h-full object-contain">
            </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right: Info Panel -->
      <div class="flex flex-col gap-5">

        <!-- Category badge + name -->
        <div>
          <span class="product-card-cat block mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
          <h1 class="font-display text-3xl md:text-4xl font-semibold text-stone-900 leading-tight mb-3">
            <?php echo htmlspecialchars($product['name']); ?>
          </h1>

          <!-- Rating -->
          <div class="flex items-center gap-3">
            <div class="stars flex">
              <?php echo renderStars((int)$product['rating']); ?>
            </div>
            <span class="text-sm font-semibold text-stone-700"><?php echo number_format((float)$product['rating'], 1); ?></span>
            <a href="#reviews" class="text-sm text-stone-400 hover:text-gold transition-colors">
              (<?php echo (int)$product['review_count']; ?> review<?php echo $product['review_count'] != 1 ? 's' : ''; ?>)
            </a>
          </div>
        </div>

        <!-- Price -->
        <div class="bg-white rounded-xl border border-stone-200 p-5">
          <?php if ($product['compare_price'] > $product['price']): ?>
            <div class="flex items-baseline gap-3 flex-wrap">
              <span class="price-current text-3xl"><?php echo formatPrice($product['price']); ?></span>
              <span class="price-original"><?php echo formatPrice($product['compare_price']); ?></span>
              <span class="inline-block bg-red-50 text-red-600 text-xs font-bold px-2 py-0.5 rounded">
                Save <?php echo formatPrice($product['compare_price'] - $product['price']); ?>
              </span>
            </div>
          <?php else: ?>
            <span class="price-current text-3xl"><?php echo formatPrice($product['price']); ?></span>
          <?php endif; ?>

          <!-- Stock status -->
          <div class="mt-3">
            <?php if ($product['stock_quantity'] > 10): ?>
              <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                In Stock
              </span>
            <?php elseif ($product['stock_quantity'] > 0): ?>
              <span class="stock-low inline-flex items-center gap-1.5 text-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Only <?php echo (int)$product['stock_quantity']; ?> left in stock
              </span>
            <?php else: ?>
              <span class="stock-out inline-flex items-center gap-1.5 text-sm">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                Out of Stock
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Short description -->
        <?php if (!empty($product['description'])): ?>
        <p class="text-stone-600 text-sm leading-relaxed line-clamp-3">
          <?php echo htmlspecialchars(substr(strip_tags($product['description']), 0, 220)); ?>...
        </p>
        <?php endif; ?>

        <!-- Specs -->
        <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 bg-stone-50 border-b border-stone-100">
            <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Specifications</span>
          </div>
          <table class="w-full text-sm">
            <?php if (!empty($product['sku'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium w-2/5">SKU</td>
              <td class="px-5 py-2.5 text-stone-800 font-semibold"><?php echo htmlspecialchars($product['sku']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['brand'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Brand</td>
              <td class="px-5 py-2.5 text-stone-800"><?php echo htmlspecialchars($product['brand']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['material'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Material</td>
              <td class="px-5 py-2.5 text-stone-800">
                <?php echo htmlspecialchars($product['metal_purity'] ?? ''); ?> <?php echo htmlspecialchars($product['material']); ?>
              </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['stone_type']) && $product['stone_type'] !== 'None'): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Stone</td>
              <td class="px-5 py-2.5 text-stone-800">
                <?php if (!empty($product['stone_weight'])): ?><?php echo htmlspecialchars($product['stone_weight']); ?>ct <?php endif; ?>
                <?php echo htmlspecialchars($product['stone_type']); ?>
              </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['weight'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Weight</td>
              <td class="px-5 py-2.5 text-stone-800"><?php echo htmlspecialchars($product['weight']); ?>g</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['gender'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Gender</td>
              <td class="px-5 py-2.5 text-stone-800"><?php echo htmlspecialchars($product['gender']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['style'])): ?>
            <tr class="border-b border-stone-50">
              <td class="px-5 py-2.5 text-stone-500 font-medium">Style</td>
              <td class="px-5 py-2.5 text-stone-800"><?php echo htmlspecialchars($product['style']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($product['occasion'])): ?>
            <tr>
              <td class="px-5 py-2.5 text-stone-500 font-medium">Occasion</td>
              <td class="px-5 py-2.5 text-stone-800"><?php echo htmlspecialchars($product['occasion']); ?></td>
            </tr>
            <?php endif; ?>
          </table>
        </div>

        <!-- Quantity + Add to Cart -->
        <?php if ($product['stock_quantity'] > 0): ?>
        <div class="bg-white rounded-xl border border-stone-200 p-5 space-y-4">
          <div class="flex items-center gap-4">
            <label class="text-sm font-semibold text-stone-700">Quantity</label>
            <div class="qty-stepper">
              <button type="button" onclick="decreaseQty()" class="qty-btn" aria-label="Decrease quantity">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
              </button>
              <input type="number" id="product-qty" class="qty-input" value="1" min="1"
                     max="<?php echo (int)$product['stock_quantity']; ?>" readonly>
              <button type="button" onclick="increaseQty()" class="qty-btn" aria-label="Increase quantity">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <span class="text-xs text-stone-400"><?php echo (int)$product['stock_quantity']; ?> available</span>
          </div>

          <button onclick="addToCartWithQty(<?php echo (int)$product['id']; ?>)"
            class="btn btn-gold btn-full flex items-center justify-center gap-2 text-base">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Add to Cart
          </button>

          <button onclick="buyNow(<?php echo (int)$product['id']; ?>)"
            class="btn btn-dark btn-full flex items-center justify-center gap-2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Buy Now
          </button>

          <?php if (isLoggedIn()): ?>
          <button onclick="toggleWishlist(<?php echo (int)$product['id']; ?>)"
            id="wishlist-btn"
            class="btn btn-outline btn-full flex items-center justify-center gap-2 <?php echo $inWishlist ? 'text-red-500 border-red-200' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="<?php echo $inWishlist ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" width="18" height="18" id="wishlist-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span id="wishlist-text"><?php echo $inWishlist ? 'Saved to Wishlist' : 'Add to Wishlist'; ?></span>
          </button>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
          This item is currently out of stock. Check back soon or browse similar pieces below.
        </div>
        <?php endif; ?>

        <!-- Trust Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="flex items-center gap-3 bg-white rounded-xl border border-stone-200 px-4 py-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" class="text-gold flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            <div>
              <p class="text-xs font-bold text-stone-800">Free Shipping</p>
              <p class="text-xs text-stone-400">Over ₦50,000</p>
            </div>
          </div>
          <div class="flex items-center gap-3 bg-white rounded-xl border border-stone-200 px-4 py-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" class="text-gold flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            <div>
              <p class="text-xs font-bold text-stone-800">Certified</p>
              <p class="text-xs text-stone-400">100% authentic</p>
            </div>
          </div>
          <div class="flex items-center gap-3 bg-white rounded-xl border border-stone-200 px-4 py-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" class="text-gold flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            <div>
              <p class="text-xs font-bold text-stone-800">30-Day Returns</p>
              <p class="text-xs text-stone-400">Easy returns</p>
            </div>
          </div>
        </div>

      </div><!-- /info panel -->
    </div><!-- /product grid -->

    <!-- ── Tabs: Description & Reviews ───────────────────── -->
    <div class="mb-16" id="reviews">
      <!-- Tab Nav -->
      <div class="flex border-b border-stone-200 mb-8 gap-1">
        <button onclick="switchTab('description')" id="tab-description"
          class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-gold text-gold -mb-px transition-colors">
          Description
        </button>
        <button onclick="switchTab('reviews')" id="tab-reviews"
          class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-stone-500 hover:text-stone-800 -mb-px transition-colors">
          Reviews (<?php echo count($reviews); ?>)
        </button>
      </div>

      <!-- Description Tab -->
      <div id="panel-description">
        <div class="glass-card max-w-3xl" style="padding:32px 36px !important;">
          <?php if (!empty($product['description'])): ?>
            <div class="prose prose-stone text-stone-700 leading-relaxed text-sm" style="max-width:none;">
              <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
          <?php else: ?>
            <p class="text-stone-400 text-sm italic">No description available for this product.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reviews Tab -->
      <div id="panel-reviews" class="hidden">

        <?php if (!empty($reviews)): ?>
          <!-- Review Summary -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Overall score -->
            <div class="glass-card text-center" style="padding:32px 24px !important;">
              <p class="font-display text-6xl font-semibold text-stone-900 mb-1">
                <?php echo number_format((float)$reviewStats['average'], 1); ?>
              </p>
              <div class="stars flex justify-center mb-2">
                <?php echo renderStars((int)round($reviewStats['average'])); ?>
              </div>
              <p class="text-sm text-stone-400"><?php echo (int)$reviewStats['total']; ?> review<?php echo $reviewStats['total'] != 1 ? 's' : ''; ?></p>
            </div>
            <!-- Breakdown bars -->
            <div class="glass-card flex flex-col justify-center gap-2" style="padding:28px 32px !important;">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php
                  $cnt = isset($reviewStats['breakdown'][$i]) ? (int)$reviewStats['breakdown'][$i] : 0;
                  $pct = $reviewStats['total'] > 0 ? round($cnt / $reviewStats['total'] * 100) : 0;
                ?>
                <div class="flex items-center gap-3 text-xs">
                  <span class="text-stone-500 w-10 text-right"><?php echo $i; ?> star</span>
                  <div class="flex-1 h-2 bg-stone-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gold rounded-full" style="width:<?php echo $pct; ?>%"></div>
                  </div>
                  <span class="text-stone-400 w-6"><?php echo $cnt; ?></span>
                </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Write Review button (only shown if user hasn't reviewed yet) -->
          <?php
          $userExistingReview = null;
          if (isLoggedIn()) {
            foreach ($reviews as $r) {
              if ((int)$r['user_id'] === (int)$_SESSION['user_id']) { $userExistingReview = $r; break; }
            }
          }
          if ($userHasPurchased && !$userExistingReview): ?>
          <div class="flex justify-end mb-6">
            <button onclick="openReviewModal()" class="btn btn-gold flex items-center gap-2">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              Write a Review
            </button>
          </div>
          <?php endif; ?>

          <!-- Review Cards -->
          <div class="space-y-4">
            <?php foreach ($reviews as $review):
              $isOwnReview = isLoggedIn() && (int)$review['user_id'] === (int)($_SESSION['user_id'] ?? 0);
            ?>
            <div class="glass-card" style="padding:24px 28px !important;">
              <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-stone-800 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    <?php echo strtoupper(substr($review['first_name'], 0, 1)); ?>
                  </div>
                  <div>
                    <p class="font-semibold text-stone-800 text-sm">
                      <?php echo htmlspecialchars($review['first_name'] . ' ' . strtoupper(substr($review['last_name'], 0, 1))); ?>.
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="text-xs text-stone-400"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                      <?php if (!empty($review['verified_purchase'])): ?>
                        <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                          <svg viewBox="0 0 20 20" fill="currentColor" width="11" height="11"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                          Verified Purchase
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="stars flex">
                    <?php echo renderStars((int)$review['rating']); ?>
                  </div>
                  <?php if ($isOwnReview): ?>
                  <button onclick="openEditReview(<?php echo $review['id']; ?>, <?php echo $review['rating']; ?>, <?php echo htmlspecialchars(json_encode($review['review_text']), ENT_QUOTES); ?>)"
                          class="text-xs font-semibold text-stone-400 hover:text-gold border border-stone-200 hover:border-gold rounded-lg px-3 py-1 transition-colors flex items-center gap-1">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="11" height="11"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                    Edit
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <p class="text-stone-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
            </div>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <!-- No reviews -->
          <div class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-4">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" class="text-stone-400"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
            </div>
            <h3 class="font-display text-xl font-semibold text-stone-800 mb-2">No reviews yet</h3>
            <p class="text-stone-400 text-sm mb-5">Be the first to review this piece.</p>
            <?php if ($userHasPurchased): ?>
              <button onclick="openReviewModal()" class="btn btn-gold">Write a Review</button>
            <?php elseif (!isLoggedIn()): ?>
              <a href="login.php" class="btn btn-outline">Sign in to review</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div><!-- /reviews panel -->
    </div>

    <!-- ── Related Products ───────────────────────────────── -->
    <?php if (!empty($relatedProducts)): ?>
    <section style="border-top:1px solid var(--cream-dark);padding-top:56px;margin-top:16px;">
      <div style="margin-bottom:36px;">
        <p class="section-eyebrow">You May Also Like</p>
        <h2 class="section-title" style="margin-bottom:10px;">Related Pieces</h2>
        <div class="section-divider"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($relatedProducts as $rp): ?>
          <div class="product-card">
            <div class="product-card-img">
              <a href="product.php?id=<?php echo (int)$rp['id']; ?>">
                <img src="<?php echo htmlspecialchars($rp['image']); ?>"
                     alt="<?php echo htmlspecialchars($rp['name']); ?>" loading="lazy">
              </a>
              <?php if ($rp['compare_price'] > $rp['price']): ?>
                <span class="product-card-badge badge-sale">Sale</span>
              <?php endif; ?>
              <div class="product-card-actions">
                <button onclick="addToCart(<?php echo (int)$rp['id']; ?>)" class="icon-btn" aria-label="Add to cart">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </button>
                <?php if (isLoggedIn()): ?>
                <button onclick="addToWishlist(<?php echo (int)$rp['id']; ?>)" class="icon-btn" aria-label="Add to wishlist">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </button>
                <?php endif; ?>
              </div>
            </div>
            <div class="product-card-body">
              <p class="product-card-cat"><?php echo htmlspecialchars($rp['category_name'] ?? 'Jewellery'); ?></p>
              <h3 class="product-card-name">
                <a href="product.php?id=<?php echo (int)$rp['id']; ?>"><?php echo htmlspecialchars($rp['name']); ?></a>
              </h3>
              <div class="stars mb-1"><?php echo renderStars((int)$rp['rating']); ?></div>
              <div class="product-card-price">
                <?php if ($rp['compare_price'] > $rp['price']): ?>
                  <span class="price-original"><?php echo formatPrice($rp['compare_price']); ?></span>
                <?php endif; ?>
                <span class="price-current"><?php echo formatPrice($rp['price']); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>
</div>

<!-- ── Review Modal ────────────────────────────────────── -->
<?php if ($userHasPurchased): ?>
<div id="review-modal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4" onclick="handleModalClick(event)">
  <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between px-6 py-5 border-b border-stone-100">
      <h3 id="review-modal-title" class="font-display text-xl font-semibold text-stone-900">Write a Review</h3>
      <button onclick="closeReviewModal()" class="icon-btn" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="review-form" onsubmit="submitReview(event)" class="px-6 py-5 space-y-5">
      <input type="hidden" id="review-id" name="review_id" value="">
      <div class="form-group">
        <label class="form-label">Rating <span class="text-red-500">*</span></label>
        <!-- Star Rating Input (flex-row-reverse trick) -->
        <div class="flex flex-row-reverse justify-end gap-1 mt-1" id="star-input">
          <?php for ($s = 5; $s >= 1; $s--): ?>
            <input type="radio" name="rating" value="<?php echo $s; ?>" id="star<?php echo $s; ?>" class="hidden" required>
            <label for="star<?php echo $s; ?>" class="cursor-pointer text-stone-300 hover:text-gold transition-colors text-4xl leading-none" style="color:#d6d3d1;">
              <svg viewBox="0 0 20 20" fill="currentColor" width="36" height="36" class="star-input-svg"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="review-text" class="form-label">Your Review <span class="text-red-500">*</span></label>
        <textarea id="review-text" name="review_text" rows="5" required minlength="10" maxlength="1000"
          placeholder="Share your experience with this piece..."
          class="form-input w-full resize-y"></textarea>
      </div>
      <button type="submit" class="btn btn-gold btn-full">Submit Review</button>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
/* Star input: highlight selected and all previous stars */
#star-input input:checked ~ label .star-input-svg,
#star-input label:hover .star-input-svg,
#star-input label:hover ~ label .star-input-svg {
  color: #CA8A04;
  fill: #CA8A04;
}
.tab-btn { outline: none; }
</style>

<script>
// Quantity
let qty = 1;
const maxQty = <?php echo (int)$product['stock_quantity']; ?>;

function increaseQty() {
  if (qty < maxQty) { qty++; document.getElementById('product-qty').value = qty; }
}
function decreaseQty() {
  if (qty > 1) { qty--; document.getElementById('product-qty').value = qty; }
}

// Gallery
function setMainImage(src, btn) {
  document.getElementById('main-product-image').src = src;
  document.querySelectorAll('.thumb-btn').forEach(b => {
    b.classList.remove('active', 'border-gold');
    b.classList.add('border-stone-200');
  });
  btn.classList.add('active', 'border-gold');
  btn.classList.remove('border-stone-200');
}

// Cart
function addToCartWithQty(productId) {
  const quantity = parseInt(document.getElementById('product-qty')?.value || 1);
  fetch('api/add-to-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({product_id: productId, quantity: quantity})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Added to cart!', 'success');
      const badge = document.getElementById('cart-count');
      if (badge && data.cart_count) badge.textContent = data.cart_count;
    } else {
      showToast(data.message || 'Failed to add to cart', 'error');
    }
  });
}

function addToCart(productId) {
  fetch('api/add-to-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({product_id: productId, quantity: 1})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) showToast('Added to cart!', 'success');
    else showToast(data.message || 'Failed', 'error');
  });
}

function buyNow(productId) {
  addToCartWithQty(productId);
  setTimeout(() => { window.location.href = 'checkout.php'; }, 600);
}

function addToWishlist(productId) {
  fetch('api/add-to-wishlist.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({product_id: productId, action: 'add'})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) showToast('Added to wishlist!', 'success');
    else showToast(data.message || 'Failed', 'error');
  });
}

// Wishlist toggle
let inWishlist = <?php echo $inWishlist ? 'true' : 'false'; ?>;
function toggleWishlist(productId) {
  const action = inWishlist ? 'remove' : 'add';
  fetch('api/add-to-wishlist.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({product_id: productId, action: action})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      inWishlist = !inWishlist;
      const btn = document.getElementById('wishlist-btn');
      const icon = document.getElementById('wishlist-icon');
      const txt  = document.getElementById('wishlist-text');
      if (inWishlist) {
        icon.setAttribute('fill', 'currentColor');
        txt.textContent = 'Saved to Wishlist';
        btn.classList.add('text-red-500', 'border-red-200');
        showToast('Added to wishlist!', 'success');
      } else {
        icon.setAttribute('fill', 'none');
        txt.textContent = 'Add to Wishlist';
        btn.classList.remove('text-red-500', 'border-red-200');
        showToast('Removed from wishlist', 'success');
      }
    }
  });
}

// Tabs
function switchTab(tab) {
  ['description', 'reviews'].forEach(t => {
    const btn   = document.getElementById('tab-' + t);
    const panel = document.getElementById('panel-' + t);
    if (t === tab) {
      btn.classList.add('border-gold', 'text-gold');
      btn.classList.remove('border-transparent', 'text-stone-500');
      panel.classList.remove('hidden');
    } else {
      btn.classList.remove('border-gold', 'text-gold');
      btn.classList.add('border-transparent', 'text-stone-500');
      panel.classList.add('hidden');
    }
  });
}

// Review modal
function openReviewModal() {
  document.getElementById('review-modal-title').textContent = 'Write a Review';
  document.getElementById('review-id').value = '';
  document.getElementById('review-form').reset();
  // Reset stars
  document.querySelectorAll('#star-input .star-input-svg').forEach(s => { s.style.color = ''; });
  const m = document.getElementById('review-modal');
  if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function openEditReview(reviewId, rating, text) {
  document.getElementById('review-modal-title').textContent = 'Edit Your Review';
  document.getElementById('review-id').value = reviewId;
  document.getElementById('review-text').value = text;
  // Pre-select the star rating
  const radio = document.getElementById('star' + rating);
  if (radio) radio.checked = true;
  const m = document.getElementById('review-modal');
  if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function closeReviewModal() {
  const m = document.getElementById('review-modal');
  if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
}
function handleModalClick(e) {
  if (e.target === e.currentTarget) closeReviewModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReviewModal(); });

function submitReview(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.append('product_id', <?php echo (int)$productId; ?>);
  fetch('api/submit-review.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Review submitted! Thank you.', 'success');
        closeReviewModal();
        setTimeout(() => location.reload(), 1200);
      } else {
        showToast(data.message || 'Failed to submit review', 'error');
      }
    })
    .catch(() => showToast('An error occurred', 'error'));
}

// Toast helper
function showToast(message, type) {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
  toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:200px;';
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// Auto-switch to reviews tab when URL contains #reviews
if (window.location.hash === '#reviews') {
  switchTab('reviews');
}
</script>

<?php require_once 'includes/footer.php'; ?>
