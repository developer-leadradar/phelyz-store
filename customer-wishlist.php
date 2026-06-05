<?php
$pageTitle = "My Wishlist";
require_once 'includes/header.php';
requireLogin();
$user          = getCurrentUser();
$wishlistItems = getWishlistItems();
$customerNav   = [
  ['customer-dashboard.php','Dashboard','M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5'],
  ['customer-orders.php','My Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
  ['customer-profile.php','Profile & Security','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
  ['customer-addresses.php','My Addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
  ['customer-wishlist.php','Wishlist','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
  ['customer-orders.php?status=delivered','My Reviews','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
  ['logout.php','Sign Out','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75'],
];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="customer-layout">
  <div>
    <div style="margin-bottom:24px;">
      <div class="breadcrumb"><a href="customer-dashboard.php">Account</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span>Wishlist</span></div>
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <h1 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);">My Wishlist <span style="font-size:18px;color:var(--stone-mid);font-weight:500;">(<?php echo count($wishlistItems); ?>)</span></h1>
        <?php if (!empty($wishlistItems)): ?><a href="shop.php" class="btn btn-outline btn-sm">Continue Shopping</a><?php endif; ?>
      </div>
    </div>

    <?php if (empty($wishlistItems)): ?>
      <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--cream-dark);">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--cream-dark);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--stone-mid)" width="30" height="30"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
        </div>
        <h3 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);margin-bottom:8px;">Your wishlist is empty</h3>
        <p style="font-size:14px;color:var(--stone-mid);margin-bottom:20px;">Save pieces you love by clicking the heart icon on any product.</p>
        <a href="shop.php" class="btn btn-gold">Explore Collection</a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px;">
        <?php foreach ($wishlistItems as $item): ?>
          <div class="product-card">
            <div class="product-card-img">
              <a href="product.php?id=<?php echo $item['id']; ?>">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy" onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
              </a>
              <div class="product-card-actions">
                <button onclick="addToCart(<?php echo $item['id']; ?>)" class="icon-btn" title="Add to Cart">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                </button>
                <button onclick="removeFromWishlistAndRefresh(<?php echo $item['id']; ?>)" class="icon-btn active" title="Remove from Wishlist">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                </button>
              </div>
            </div>
            <div class="product-card-body">
              <div class="product-card-cat"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></div>
              <h3 class="product-card-name"><a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
              <div class="product-card-price">
                <span class="price-current"><?php echo formatPrice($item['price']); ?></span>
                <?php if (!empty($item['compare_price']) && $item['compare_price']>$item['price']): ?><span class="price-original"><?php echo formatPrice($item['compare_price']); ?></span><?php endif; ?>
              </div>
              <button onclick="addToCart(<?php echo $item['id']; ?>)" class="btn btn-gold btn-sm btn-full" style="margin-top:12px;">Add to Cart</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<script>
async function removeFromWishlistAndRefresh(productId) {
  try {
    const res  = await fetch('/phelyz-store/api/add-to-wishlist.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId, action: 'remove' })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Removed from wishlist', 'success');
      setTimeout(function(){ location.reload(); }, 600);
    } else {
      showToast(data.message || 'Could not remove', 'error');
    }
  } catch(e) {
    showToast('Network error', 'error');
  }
}
</script>
<?php require_once 'includes/footer.php'; ?>
