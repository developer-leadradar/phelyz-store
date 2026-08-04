<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

// Several pages define their own renderStars(); provide one only if absent so
// this partial also works standalone (e.g. from the infinite-scroll endpoint).
if (!function_exists('renderStars')) {
    function renderStars($r) {
        $o = '';
        for ($i = 1; $i <= 5; $i++) {
            $o .= '<svg class="' . ($i <= $r ? 'star-on' : 'star-off') . '" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        }
        return $o;
    }
}

/**
 * Renders one product card.
 *
 * Shared by shop.php (first page, server-rendered) and
 * api/load-products.php (subsequent pages loaded by infinite scroll), so both
 * produce byte-identical markup and can never drift apart.
 */
function renderProductCard(array $p): void {
    $eff = effectiveStockStatus($p);
    ?>
    <div class="product-card" onclick="window.location='product.php?id=<?php echo (int)$p['id']; ?>'">
      <div class="product-card-img">
        <img src="<?php echo htmlspecialchars($p['image']); ?>"
             alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"
             onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
        <?php if ($p['compare_price'] > $p['price']): ?>
          <span class="product-card-badge badge-sale">Sale</span>
        <?php elseif (!empty($p['is_featured'])): ?>
          <span class="product-card-badge badge-featured">Featured</span>
        <?php endif; ?>
        <?php if ($eff === 'express'): ?>
          <span class="product-card-badge" style="top:auto;bottom:10px;left:10px;right:auto;background:#D97706;color:white;font-size:9px;padding:3px 8px;border-radius:20px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">Express</span>
        <?php elseif ($eff === 'preorder'): ?>
          <span class="product-card-badge" style="top:auto;bottom:10px;left:10px;right:auto;background:#D97706;color:white;font-size:9px;padding:3px 8px;border-radius:20px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">Express</span>
        <?php endif; ?>
        <div class="product-card-actions">
          <button onclick="event.stopPropagation();addToCart(<?php echo (int)$p['id']; ?>)" class="icon-btn" title="Add to Cart">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
          </button>
          <?php if (isLoggedIn()): ?>
          <button onclick="event.stopPropagation();addToWishlist(<?php echo (int)$p['id']; ?>)" class="icon-btn" title="Wishlist">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="product-card-body">
        <div class="product-card-cat"><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></div>
        <h3 class="product-card-name"><a href="product.php?id=<?php echo (int)$p['id']; ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($p['name']); ?></a></h3>
        <?php if (!empty($p['material'])): ?><div class="product-card-meta"><?php echo htmlspecialchars(trim(($p['metal_purity'] ?? '').' '.$p['material'])); ?></div><?php endif; ?>
        <div class="stars" style="margin-bottom:8px;"><?php echo renderStars((int)$p['rating']); ?><span style="font-size:11px;color:var(--stone-mid);margin-left:4px;">(<?php echo (int)$p['review_count']; ?>)</span></div>
        <div class="product-card-price">
          <span class="price-current"><?php echo formatPrice($p['price']); ?></span>
          <?php if ($p['compare_price'] > $p['price']): ?><span class="price-original"><?php echo formatPrice($p['compare_price']); ?></span><?php endif; ?>
        </div>
        <?php if ($eff === 'preorder' || $eff === 'express'): ?>
          <div style="font-size:11px;font-weight:700;color:#D97706;margin-top:4px;">Express</div>
        <?php elseif ($p['stock_quantity'] <= 5 && $p['stock_quantity'] > 0): ?>
          <div class="stock-low">Only <?php echo (int)$p['stock_quantity']; ?> left!</div>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
